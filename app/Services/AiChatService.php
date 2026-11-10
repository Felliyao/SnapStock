<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatService
{
    public function isConfigured(): bool
    {
        $apiKey = config('services.gemini.api_key');

        return is_string($apiKey) && trim($apiKey) !== '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $userContent
     * @return array{ok: bool, content: ?string, status: int, message: ?string}
     */
    public function complete(string $systemPrompt, array $userContent, bool $jsonResponse = true): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'content' => null,
                'status' => 503,
                'message' => 'Fitur AI belum dikonfigurasi. Tambahkan GEMINI_API_KEY pada file .env.',
            ];
        }

        $payload = [
            'model' => config('services.gemini.model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                ['role' => 'user', 'content' => $userContent],
            ],
        ];

        if ($jsonResponse) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::acceptJson()
                ->withToken(config('services.gemini.api_key'))
                ->connectTimeout(5)
                ->timeout(60)
                ->post(rtrim((string) config('services.gemini.base_url'), '/').'/chat/completions', $payload);
        } catch (ConnectionException $exception) {
            Log::warning('AI request could not connect to the provider.', [
                'exception' => $exception::class,
            ]);

            return [
                'ok' => false,
                'content' => null,
                'status' => 503,
                'message' => 'Layanan AI tidak dapat dihubungi saat ini. Periksa koneksi internet lalu coba lagi.',
            ];
        }

        if (! $response->successful()) {
            Log::warning('AI provider rejected a request.', [
                'status' => $response->status(),
            ]);

            return [
                'ok' => false,
                'content' => null,
                'status' => 502,
                'message' => in_array($response->status(), [401, 403], true)
                    ? 'Kunci AI tidak valid. Periksa konfigurasi GEMINI_API_KEY.'
                    : 'AI gagal membuat hasil. Silakan coba lagi.',
            ];
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            Log::warning('AI provider returned an empty response.');

            return [
                'ok' => false,
                'content' => null,
                'status' => 502,
                'message' => 'AI tidak mengembalikan hasil yang dapat digunakan. Silakan coba lagi.',
            ];
        }

        return [
            'ok' => true,
            'content' => $content,
            'status' => 200,
            'message' => null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function parseJson(string $content): ?array
    {
        if (preg_match('/\{.*\}/s', $content, $matches) === 1) {
            $content = $matches[0];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
