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

    /** Load the configured models into memory so the first student request is not a cold start. */
    public function warm(): array
    {
        return collect([$this->modelName(), $this->chatModelName()])->filter()->unique()->mapWithKeys(function (string $model) {
            $started = microtime(true);
            try {
                Http::timeout(180)->post(rtrim(config('services.ollama.url'), '/').'/api/generate', [
                    'model' => $model, 'keep_alive' => config('services.ollama.keep_alive', '30m'),
                ])->throw();
                return [$model => round(microtime(true) - $started, 1)];
            } catch (Throwable $exception) {
                report($exception);
                return [$model => null];
            }
        })->all();
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
                    'keep_alive' => config('services.ollama.keep_alive', '30m'),
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
        $clean = preg_replace('/^`{3}(?:json)?\s*|\s*`{3}$/i', '', $clean) ?? $clean;
        $decoded = json_decode($clean, true);
        if (! is_array($decoded)) {
            $start = strpos($clean, '{');
            $end = strrpos($clean, '}');
            $decoded = $start !== false && $end > $start ? json_decode(substr($clean, $start, $end - $start + 1), true) : null;
        }

        // Parsed JSON that contains none of the requested keys (e.g. an echoed prompt) is still a failed request.
        $required = $schema['required'] ?? [];
        $valid = is_array($decoded) && (! $required || collect($required)->contains(fn ($key) => array_key_exists($key, $decoded)));
        app(SystemEventLog::class)->record('ai', $valid ? 'completed' : 'failed', $valid
            ? 'Local AI returned a structured response.'
            : 'Local AI returned an invalid response; the labelled fallback was used.');

        return $valid ? $decoded : null;
    }
}
