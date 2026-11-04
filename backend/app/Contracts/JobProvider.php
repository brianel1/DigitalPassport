<?php

namespace App\Contracts;

interface JobProvider
{
    public function source(): string;

    public function isConfigured(): bool;

    /** @return array<int, array<string, mixed>> */
    public function fetch(string $keywords, string $location): array;
}
