<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class CvOcr
{
    public function python(): string
    {
        return config('services.ocr.python') ?: base_path('../.venv-ocr311/'.(PHP_OS_FAMILY === 'Windows' ? 'Scripts/python.exe' : 'bin/python'));
    }
    public function available(): bool { return is_file($this->python()); }
    public function extract(string $path): array
    {
        if (!$this->available()) throw ValidationException::withMessages(['file' => 'This PDF needs text recognition, which is not installed on this server. Paste your CV text or upload a text-based PDF / DOCX.']);
        $process = new Process([$this->python(), base_path('../scripts/ocr_cv.py'), $path]);
        $process->setTimeout(120);
        try {
            $process->run();
            $data = json_decode($process->getOutput(), true);
            if (!$process->isSuccessful() || !isset($data['text'])) throw new \RuntimeException($data['error'] ?? 'Text recognition could not finish. Try a clearer PDF or paste your CV text.');
            return $data;
        } catch (\Throwable $error) {
            app(SystemEventLog::class)->record('ocr', 'failed', 'Local PDF text recognition failed.');
            throw ValidationException::withMessages(['file' => $error instanceof \Symfony\Component\Process\Exception\ProcessTimedOutException ? 'Text recognition took too long. Upload fewer pages or paste the text instead.' : $error->getMessage()]);
        }
    }
}
