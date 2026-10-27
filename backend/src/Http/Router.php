<?php

declare(strict_types=1);

namespace DigitalPassport\Http;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            JsonResponse::send(['error' => 'Not found'], 404);
            return;
        }

        $handler();
    }
}
