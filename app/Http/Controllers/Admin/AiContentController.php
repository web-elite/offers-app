<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Support\Settings;
use App\Services\LlmService;
use Illuminate\Http\Request;
use App\Services\TelegramApi;
use App\Models\TelegramMessage;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessTelegramPostJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View as ViewContract;

/**
 * Admin dashboard for the Telegram -> LLM -> Offer pipeline.
 *
 * Routes:
 *   GET  /admin/ai-content              list telegram messages + results
 *   POST /admin/ai-content/{id}/retry   re-dispatch the LLM job for one message
 *   GET  /admin/ai-content/settings     llm + telegram settings (editable in admin)
 *   PUT  /admin/ai-content/settings     update llm + telegram settings
 */
class AiContentController extends Controller
{
    public function index(Request $request): ViewContract
    {
        $this->authorizeContent();

        $status = (string) $request->query('status', '');
        $q = $request->query('q', '');

        $messages = TelegramMessage::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where('text', 'like', $like)
                    ->orWhere('sender_name', 'like', $like)
                    ->orWhere('url', 'like', $like);
            })
            ->with(['aiAnalysisResult', 'offer'])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'all' => TelegramMessage::count(),
            'pending' => TelegramMessage::where('status', TelegramMessage::STATUS_PENDING)->count(),
            'success' => TelegramMessage::where('status', TelegramMessage::STATUS_SUCCESS)->count(),
            'failed' => TelegramMessage::where('status', TelegramMessage::STATUS_FAILED)->count(),
            'skipped' => TelegramMessage::where('status', TelegramMessage::STATUS_SKIPPED)->count(),
        ];

        return view('admin.ai-content.index', [
            'messages' => $messages,
            'status' => $status,
            'counts' => $counts,
            'statuses' => TelegramMessage::STATUSES,
        ]);
    }

    /**
     * Show one message with its AI result and the offer that was created.
     */
    public function show(int $id): ViewContract
    {
        $this->authorizeContent();

        $message = TelegramMessage::query()
            ->with(['aiAnalysisResult', 'offer.provider'])
            ->findOrFail($id);

        return view('admin.ai-content.show', [
            'message' => $message,
        ]);
    }

    /**
     * Re-dispatch the LLM processing for a single message.
     * Useful if the LLM was misconfigured at the time of first processing,
     * or the user wants to regenerate the offer.
     */
    public function retry(int $id): RedirectResponse
    {
        $this->authorizeContent();

        $message = TelegramMessage::query()->findOrFail($id);

        // Reset to pending so the UI reflects a fresh run.
        $message->forceFill([
            'status' => TelegramMessage::STATUS_PENDING,
            'error' => null,
            'offer_id' => null,
        ])->save();

        ProcessTelegramPostJob::dispatch($message);

        return back()->with('status', 'آفر جدید در صف قرار گرفت.');
    }

    /**
     * LLM + Telegram settings page.
     */
    public function settings(Request $request): ViewContract
    {
        $this->authorizeContent();

        $values = [
            Settings::LLM_BASE_URL => Settings::get(Settings::LLM_BASE_URL),
            Settings::LLM_API_KEY => Settings::get(Settings::LLM_API_KEY, ''),
            Settings::LLM_MODEL => Settings::get(Settings::LLM_MODEL),
            Settings::LLM_TEMPERATURE => Settings::get(Settings::LLM_TEMPERATURE, '0'),
            Settings::TELEGRAM_BOT_TOKEN => Settings::get(Settings::TELEGRAM_BOT_TOKEN, ''),
            Settings::TELEGRAM_WEBHOOK_SECRET => Settings::get(Settings::TELEGRAM_WEBHOOK_SECRET, ''),
            Settings::TELEGRAM_ALLOWED_CHAT_IDS => Settings::get(Settings::TELEGRAM_ALLOWED_CHAT_IDS, ''),
        ];

        return view('admin.ai-content.settings', [
            'values' => $values,
            'isTesting' => (bool) $request->query('testing', false),
            'testResult' => $request->query('test_result'),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->authorizeContent();

        $data = $request->validate([
            Settings::LLM_BASE_URL => ['required', 'string', 'url', 'max:2048'],
            Settings::LLM_API_KEY => ['nullable', 'string', 'max:2048'],
            Settings::LLM_MODEL => ['required', 'string', 'max:191'],
            Settings::LLM_TEMPERATURE => ['nullable', 'numeric', 'min:0', 'max:2'],
            Settings::TELEGRAM_BOT_TOKEN => ['nullable', 'string', 'max:255'],
            Settings::TELEGRAM_WEBHOOK_SECRET => ['nullable', 'string', 'max:255'],
            Settings::TELEGRAM_ALLOWED_CHAT_IDS => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($data as $key => $value) {
            \App\Models\Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Settings::flush();

        return back()->with('status', 'تنظیمات ذخیره شد.');
    }

    /**
     * "Test LLM connection" button on the settings page.
     */
    public function testLlm(LlmService $llm): RedirectResponse
    {
        $this->authorizeContent();

        try {
            $llm->analyze(
                'Reply with the JSON: {"pong": 1}',
                'Ping.',
            );

            return back()
                ->with('status', 'اتصال به LLM موفق بود.')
                ->with('test_result', 'ok');
        } catch (\Throwable $e) {
            return back()
                ->with('error', 'اتصال به LLM شکست خورد: '.$e->getMessage())
                ->with('test_result', 'fail');
        }
    }

    /**
     * Register the webhook on the Telegram side.
     */
    public function registerWebhook(TelegramApi $api): RedirectResponse
    {
        $this->authorizeContent();

        $secret = (string) Settings::get(Settings::TELEGRAM_WEBHOOK_SECRET, '');
        $url = rtrim((string) config('app.url'), '/').'/telegram/webhook';

        $result = $api->setWebhook($url, $secret);

        if (($result['ok'] ?? false) === true) {
            return back()->with('status', 'Webhook با موفقیت روی تلگرام ثبت شد.');
        }

        return back()->with('error', 'ثبت Webhook شکست خورد: '.json_encode($result));
    }

    private function authorizeContent(): void
    {
        $admin = auth('admin')->user();

        abort_unless($admin instanceof Admin && $admin->hasAbility('manageContent'), 403);
    }
}
