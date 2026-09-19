<?php

namespace App\Services;

use RuntimeException;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for an OpenAI-compatible /chat/completions endpoint.
 *
 * Config (all editable from the admin Settings page):
 *   - llm_base_url   (must end with /v1 or be the host root of /v1)
 *   - llm_api_key
 *   - llm_model
 *   - llm_temperature
 *
 * Works with OpenAI, OpenRouter, DeepSeek, Ollama, vLLM, LM Studio,
 * any LiteLLM proxy — anything that implements the OpenAI wire format.
 */
class LlmService
{
    /**
     * @param string $systemPrompt  system role
     * @param string $userPrompt    user role
     * @return array<string,mixed>  parsed JSON object returned by the model
     */
    public function analyze(string $systemPrompt, string $userPrompt): array
    {
        $baseUrl = rtrim((string) Settings::get(Settings::LLM_BASE_URL, 'https://api.openai.com/v1'), '/');
        $apiKey = (string) Settings::get(Settings::LLM_API_KEY, '');
        $model = (string) Settings::get(Settings::LLM_MODEL, 'gpt-4o-mini');
        $temperature = (float) Settings::get(Settings::LLM_TEMPERATURE, '0');

        if ($baseUrl === '' || $apiKey === '' || $model === '') {
            throw new RuntimeException(
                'LLM is not configured. Set llm_base_url / llm_api_key / llm_model in admin settings.'
            );
        }

        $response = Http::timeout(120)
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl.'/chat/completions', $this->body($systemPrompt, $userPrompt, $model, $temperature))
            ->throw();

        $json = $response->json();
        $content = $json['choices'][0]['message']['content'] ?? null;

        if (! is_string($content)) {
            throw new RuntimeException('LLM returned an unexpected response shape.');
        }

        return $this->parseJsonBlock($content);
    }

    private function body(string $systemPrompt, string $userPrompt, string $model, float $temperature): array
    {
        return [
            'model' => $model,
            'temperature' => $temperature,
            // Most OpenAI-compatible providers support response_format=json_object.
            // We also instruct the model via the system prompt to only emit JSON.
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];
    }

    private function parseJsonBlock(string $raw): array
    {
        $raw = trim($raw);

        // Fast path: it's already a clean JSON object.
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Common pattern: model wraps JSON in a ```json fence.
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $raw, $m)) {
            $decoded = json_decode($m[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Last resort: find the first balanced { ... }.
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($raw, $start, $end - $start + 1), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('Could not parse the LLM response as JSON: '.$raw);
    }
}
