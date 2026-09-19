<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Key/value site settings with a per-request static cache.
 *
 * Safe to call before the settings table exists (returns the default),
 * so migrations and public pages never explode during deploy.
 */
class Settings
{
    public const SITE_TITLE = 'site_title_fa';
    public const ADS_ENABLED = 'ads_enabled';
    public const ADS_INTERVAL = 'ads_interval';
    public const FOOTER_TEXT = 'footer_text_fa';

    // ---- LLM (OpenAI-compatible) ----
    public const LLM_BASE_URL = 'llm_base_url';
    public const LLM_API_KEY = 'llm_api_key';
    public const LLM_MODEL = 'llm_model';
    public const LLM_TEMPERATURE = 'llm_temperature';

    // ---- Telegram bot ----
    public const TELEGRAM_BOT_TOKEN = 'telegram_bot_token';
    public const TELEGRAM_WEBHOOK_SECRET = 'telegram_webhook_secret';
    public const TELEGRAM_ALLOWED_CHAT_IDS = 'telegram_allowed_chat_ids';

    public const DEFAULTS = [
        self::SITE_TITLE => 'آفرهای رایگان AI',
        self::ADS_ENABLED => '0',
        self::ADS_INTERVAL => '4',
        self::FOOTER_TEXT => '',
        // OpenAI-compatible endpoint (defaults to the real OpenAI; point to any
        // Ollama / vLLM / LM Studio / DeepSeek / OpenRouter proxy etc.)
        self::LLM_BASE_URL => 'https://api.openai.com/v1',
        self::LLM_API_KEY => '',
        self::LLM_MODEL => 'gpt-4o-mini',
        self::LLM_TEMPERATURE => '0',
        self::TELEGRAM_BOT_TOKEN => '',
        self::TELEGRAM_WEBHOOK_SECRET => '',
        // Comma-separated list of chat ids allowed to post; empty = allow any.
        self::TELEGRAM_ALLOWED_CHAT_IDS => '',
    ];

    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        $default ??= self::DEFAULTS[$key] ?? null;

        $all = self::all();

        if (! array_key_exists($key, $all)) {
            return $default;
        }

        $value = $all[$key];

        return $value === null || $value === '' ? $default : $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int) self::get($key, (string) $default);
    }

    public static function put(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }

    /** @return array<string, string|null> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            self::$cache = Setting::query()->pluck('value', 'key')->all();
        } catch (\Throwable) {
            // Table missing (e.g. during first migrate) — fall back to defaults.
            self::$cache = [];
        }

        return self::$cache;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
