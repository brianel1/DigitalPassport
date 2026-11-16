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

    public function chatModelName(): ?string
    {
        return config('services.ollama.chat_model') ?: $this->modelName();
    }

    /** @return array<string, mixed>|null */
    public function generateJson(string $prompt, ?array $schema = null, ?int $maxTokens = null, ?array $messages = null): ?array
    {
        if (! ($messages ? filled($this->chatModelName()) : $this->isConfigured())) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.ollama.timeout', 90))
                ->post(rtrim(config('services.ollama.url'), '/').($messages ? '/api/chat' : '/api/generate'), [
                    'model' => $messages ? $this->chatModelName() : $this->modelName(),
                    ...($messages ? ['messages'=>$messages] : ['prompt'=>$prompt]),
                    'stream' => false,
                    'think' => false,
                    'format' => $schema ?? 'json',
                    'options' => array_filter([
                        'temperature' => 0.2,
                        'num_predict' => $maxTokens,
                        // Keep small requests on the laptop GPU. Long documents receive a larger window.
                        'num_ctx' => mb_strlen($messages ? json_encode($messages) : $prompt) > 12000 ? 8192 : 4096,
                    ], fn ($value) => $value !== null),
                ])
                ->throw()
                ->json();
            $response = $messages ? ($response['message']['content'] ?? $response['response'] ?? null) : ($response['response'] ?? null);
        } catch (Throwable $exception) {
            report($exception);
            app(SystemEventLog::class)->record('ai','failed','Local AI could not complete a request; the labelled fallback was used.');
            return null;
        }

        if (! is_string($response) || trim($response) === '') {
            app(SystemEventLog::class)->record('ai', 'failed', 'Local AI returned an empty response; the labelled fallback was used.');
            return null;
        }

        $clean = trim($response);
        $clean = preg_replace('/^\x60{3}(?:json)?\s*|\s*\x60{3}$/i', '', $clean) ?? $clean;
        $decoded = json_decode($clean, true);

        if (is_array($decoded)) {
            app(SystemEventLog::class)->record('ai','completed','Local AI returned a structured response.');
            return $decoded;
        }

        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');
        if ($start === false || $end === false || $end <= $start) {
            app(SystemEventLog::class)->record('ai', 'failed', 'Local AI returned an invalid response; the labelled fallback was used.');
            return null;
        }

        $decoded = json_decode(substr($clean, $start, $end - $start + 1), true);

        app(SystemEventLog::class)->record('ai',is_array($decoded)?'completed':'failed',is_array($decoded)?'Local AI returned a structured response.':'Local AI returned an invalid response; the labelled fallback was used.');
        return is_array($decoded) ? $decoded : null;
    }
}
