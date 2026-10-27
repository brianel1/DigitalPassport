<?php

declare(strict_types=1);

use DigitalPassport\Controllers\HealthController;
use DigitalPassport\Http\Router;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$router = new Router();
$router->get('/api/health', new HealthController());
$router->dispatch($method, $path);
