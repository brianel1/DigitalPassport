<?php
require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$result = $app->make(App\Services\OllamaClient::class)->generateJson('Return JSON only: {"status":"ready"}');
echo $result && isset($result['status']) ? "Ollama JSON integration OK\n" : "Ollama JSON integration failed\n";
