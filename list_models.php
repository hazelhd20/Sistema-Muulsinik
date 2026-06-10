<?php

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $response = Gemini::models()->list();
    foreach ($response->models as $model) {
        echo $model->name."\n";
    }
} catch (Throwable $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
