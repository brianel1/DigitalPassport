<?php

namespace App\Console\Commands;

use App\Services\OllamaClient;
use Illuminate\Console\Command;

class WarmAi extends Command
{
    protected $signature = 'ai:warm';

    protected $description = 'Load the configured Ollama models before a demo or class session';

    public function handle(OllamaClient $ollama): int
    {
        if (! $ollama->isConfigured()) {
            $this->warn('OLLAMA_MODEL is not set; nothing to warm.');
            return self::FAILURE;
        }
        $failed = false;
        foreach ($ollama->warm() as $model => $seconds) {
            $seconds === null ? $this->error("{$model}: could not be loaded. Is `ollama serve` running?") : $this->info("{$model}: ready ({$seconds}s).");
            $failed = $failed || $seconds === null;
        }
        if (count(array_unique(array_filter([config('services.ollama.model'), config('services.ollama.chat_model')]))) > 1) {
            $this->line('Two different models are configured. On a GPU with limited memory they replace each other, so switching between job/CV and chat features can be slow.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
