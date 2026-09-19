<?php

namespace App\Services;

use RuntimeException;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;

/**
 * Minimal Telegram Bot API wrapper.
 *
 * Used for two things:
 *   1. Fetch a post (by chat + message id) so we can read a channel
 *      post the user forwarded to us.
 *   2. Reply to the user ("✅ انجام شد") after processing.
 */
class TelegramApi
{
    private const API_BASE = 'https://api.telegram.org';

    /**
     * Get the bot token from admin settings.
     *
     * @throws RuntimeException when not configured
     */
    public function token(): string
    {
        $token = (string) Settings::get(Settings::TELEGRAM_BOT_TOKEN, '');

        if ($token === '') {
            throw new RuntimeException('telegram_bot_token is not set. Configure it in admin settings.');
        }

        return $token;
    }

    /**
     * Send a plain text message to a chat (user or channel).
     */
    public function sendMessage(string $chatId, string $text): array
    {
        $response = Http::timeout(30)
            ->post(self::API_BASE.'/bot'.$this->token().'/sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);

        return $response->json() ?? [];
    }

    /**
     * Register the webhook endpoint for this bot.
     *
     * @param string $url  absolute URL to POST telegram updates to
     * @param string $secret  a secret that telegram appends as X-Telegram-Bot-Api-Secret-Token
     */
    public function setWebhook(string $url, string $secret = ''): array
    {
        $payload = ['url' => $url];
        if ($secret !== '') {
            $payload['secret_token'] = $secret;
        }

        $response = Http::timeout(30)
            ->post(self::API_BASE.'/bot'.$this->token().'/setWebhook', $payload);

        return $response->json() ?? [];
    }
}
