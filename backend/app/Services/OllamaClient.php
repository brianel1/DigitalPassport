<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class OllamaClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.ollama.model'));
    }

    public function modelName(): ?string
    {
        return config('services.ollama.model');
    }

    /** @return array<string, mixed>|null */
    public function generateJson(string $prompt): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.ollama.timeout', 90))
                ->post(rtrim(config('services.ollama.url'), '/').'/api/generate', [
                    'model' => $this->modelName(),
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                    'options' => [
                        'temperature' => 0.2,
                    ],
                ])
                ->throw()
                ->json('response');
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }

        if (! is_string($response) || trim($response) === '') {
            return null;
        }

        $clean = trim($response);
        $clean = preg_replace('/^\x60{3}(?:json)?\s*|\s*\x60{3}$/i', '', $clean) ?? $clean;
        $decoded = json_decode($clean, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $decoded = json_decode(substr($clean, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }
}
