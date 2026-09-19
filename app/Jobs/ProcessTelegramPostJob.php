<?php

namespace App\Jobs;

use Throwable;
use App\Services\LlmService;
use App\Services\TelegramApi;
use Illuminate\Bus\Queueable;
use App\Models\TelegramMessage;
use App\Models\AiAnalysisResult;
use App\Services\AiContentService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Take one TelegramMessage, send its text to the LLM, persist the
 * structured result, materialize it into an Offer (status = active),
 * and notify the user on Telegram.
 *
 * Runs as a queued job so the webhook returns fast.
 */
class ProcessTelegramPostJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public TelegramMessage $message,
    ) {}

    public function handle(
        LlmService $llm,
        AiContentService $aiContent,
        TelegramApi $telegram,
    ): void {
        $message = $this->message;

        if (trim((string) $message->text) === '') {
            $message->forceFill([
                'status' => TelegramMessage::STATUS_SKIPPED,
                'error' => 'No text to analyze.',
            ])->save();

            return;
        }

        $analysis = $this->analyze($message, $llm);

        $result = AiAnalysisResult::create([
            'telegram_message_id' => $message->id,
            'model' => \App\Support\Settings::get(\App\Support\Settings::LLM_MODEL),
            'response' => $analysis,
            'provider_name' => $analysis['provider_name'] ?? null,
            'provider_slug' => $analysis['provider_slug'] ?? null,
            'provider_url' => $analysis['provider_url'] ?? null,
            'offer_title' => $analysis['offer_title'] ?? null,
            'offer_description' => $analysis['offer_description'] ?? null,
            'free_tier_type' => $analysis['free_tier_type'] ?? null,
            'access_types' => $analysis['access_types'] ?? [],
            'credits_amount' => isset($analysis['credits_amount']) ? (int) $analysis['credits_amount'] : null,
            'credits_unit' => $analysis['credits_unit'] ?? null,
            'pricing_type' => $analysis['pricing_type'] ?? null,
            'offer_slug' => $analysis['offer_slug'] ?? null,
            'materialized' => false,
        ]);

        $message->forceFill(['ai_analysis_result_id' => $result->id])->save();

        // Materialize into the content model.
        $offer = $aiContent->materialize($analysis, $message, $result);

        $message->forceFill([
            'status' => TelegramMessage::STATUS_SUCCESS,
            'offer_id' => $offer->id,
        ])->save();

        // Best-effort notification back on telegram.
        try {
            $this->notifyUser($telegram, $message, $offer);
        } catch (Throwable) {
            // Ignore: the content is already saved; notification is optional.
        }
    }

    /**
     * Call the LLM with a tightly specified prompt so the response is a
     * JSON object with the exact keys we materialize from.
     *
     * @return array<string,mixed>
     */
    private function analyze(TelegramMessage $message, LlmService $llm): array
    {
        $system = 'You are an expert content analyst for a directory of free AI offers. '
            .'You will be given a post (usually from a Telegram channel). '
            .'Extract the free-AI offer it describes. '
            .'Respond with ONLY a JSON object — no prose, no markdown — using exactly these keys: '
            .'provider_name, provider_slug, provider_url, provider_description, '
            .'offer_title, offer_description, offer_slug, '
            .'free_tier_type (one of: forever_free, daily_reset, monthly_credit, signup_bonus, referral_bonus, trial, free_models, free_credits, unknown), '
            .'pricing_type (one of: free, freemium, trial, pay_as_you_go, subscription, or null), '
            .'credits_amount (integer or null), '
            .'credits_unit (one of: usd, credit, token, model, domain, or null), '
            .'access_types (array of: api, web, chat_ui, ide, telegram_bot, unknown), '
            .'offer_status (default "active"; use "manual_review" only if the offer seems suspicious).';

        $user = "Analyze the following post and extract the structured offer.\n\n"
            .($message->url ? 'URL: '.$message->url."\n\n" : '')
            .'Post text:'.PHP_EOL
            .$message->text;

        $analysis = $llm->analyze($system, $user);

        // Force offer_status to active per the user's requirement.
        $analysis['offer_status'] = 'active';

        return $analysis;
    }

    private function notifyUser(TelegramApi $telegram, TelegramMessage $message, \App\Models\Offer $offer): void
    {
        if ($message->chat_id === null) {
            return;
        }

        $title = mb_strimwidth($offer->title_fa, 0, 80, '…');

        $telegram->sendMessage(
            (string) $message->chat_id,
            "✅ آفر «{$title}» با موفقیت ایجاد شد."
            ."\n".'ارائه‌دهنده: '.($offer->provider->name ?? '—')
            ."\n".'وضعیت: '.$offer->status,
        );
    }
}
