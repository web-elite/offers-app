<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\Http\Request;
use App\Services\TelegramApi;
use App\Models\TelegramMessage;
use Illuminate\Http\JsonResponse;
use App\Jobs\ProcessTelegramPostJob;

/**
 * Inbound webhook from Telegram.
 *
 *   POST /telegram/webhook
 *
 * Telegram sends `message` updates here. For each message we:
 *   1. Persist a TelegramMessage row (pending).
 *   2. Dispatch a ProcessTelegramPostJob that will:
 *        - call the LLM,
 *        - materialize an Offer,
 *        - reply to the user on telegram.
 *
 * The webhook is guarded by a `secret_token` (set on the Telegram side via
 * setWebhook) so only the real bot can POST to us.
 */
class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramApi $telegram,
    ) {}

    public function webhook(Request $request): JsonResponse
    {
        // 1. Verify the telegram secret_token (if configured).
        $expectedSecret = (string) Settings::get(Settings::TELEGRAM_WEBHOOK_SECRET, '');
        if ($expectedSecret !== '') {
            $actual = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (! hash_equals($expectedSecret, $actual)) {
                abort(403, 'Invalid Telegram secret token.');
            }
        }

        $update = $request->json()->all();

        // 2. We only care about `message` updates.
        $message = $update['message'] ?? null;
        if (! is_array($message)) {
            // Acknowledge so Telegram doesn't retry.
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $chatId = (string) ($message['chat']['id'] ?? ($message['from']['id'] ?? ''));
        $messageId = (string) ($message['message_id'] ?? '');
        $sender = (string) ($message['from']['first_name'] ?? ($message['from']['username'] ?? ''));

        // Enforce an allow-list of chat ids if configured.
        $allowed = array_filter(array_map('trim', explode(',', (string) Settings::get(Settings::TELEGRAM_ALLOWED_CHAT_IDS, ''))));
        if ($allowed !== [] && ! in_array($chatId, $allowed, true)) {
            return response()->json(['ok' => false, 'error' => 'chat not allowed']);
        }

        // 3. The text to analyze:
        //    - if the user forwarded a channel post, use the forwarded text
        //    - otherwise use the message text as-is
        $text = $message['forward_from']['name'] ?? null;
        $forwardedText = (string) ($message['text'] ?? '');
        $url = $this->extractUrl($forwardedText);

        $tm = TelegramMessage::create([
            'telegram_message_id' => $messageId !== '' ? $messageId : null,
            'chat_id' => $chatId,
            'sender_name' => $sender !== '' ? $sender : null,
            'raw_payload' => $update,
            'text' => $forwardedText,
            'url' => $url,
            'status' => TelegramMessage::STATUS_PENDING,
        ]);

        // 4. Dispatch the async job — webhook returns fast.
        ProcessTelegramPostJob::dispatch($tm);

        return response()->json(['ok' => true, 'message_id' => $tm->id]);
    }

    /**
     * Optional admin helper: register the webhook with Telegram.
     *
     *   GET /telegram/register-webhook?secret=...
     *
     * Call once after you've configured the settings. The bot then starts
     * sending updates to /telegram/webhook.
     */
    public function registerWebhook(Request $request): JsonResponse
    {
        $secret = (string) $request->query('secret', Settings::get(Settings::TELEGRAM_WEBHOOK_SECRET, ''));

        // Use the app's public URL + the webhook path.
        $url = rtrim((string) config('app.url'), '/').'/telegram/webhook';

        $result = $this->telegram->setWebhook($url, $secret);

        return response()->json([
            'url' => $url,
            'result' => $result,
        ]);
    }

    private function extractUrl(string $text): ?string
    {
        // Naive: first http(s) link in the text.
        if (preg_match('/https?:\/\/\S+/i', $text, $m)) {
            return $m[0];
        }

        return null;
    }
}
