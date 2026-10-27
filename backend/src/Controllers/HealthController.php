<?php

declare(strict_types=1);

namespace DigitalPassport\Controllers;

use DigitalPassport\Http\JsonResponse;

final class HealthController
{
    public function __invoke(): void
    {
        JsonResponse::send([
            'status' => 'ok',
            'service' => 'digitalpassport-api',
        ]);
    }
}
