<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$gemini = app(\App\Services\AI\GeminiStructurerService::class);

echo "Gemini available: " . ($gemini->isAvailable() ? 'YES' : 'NO') . PHP_EOL;

$testText = <<<TEXT
MATERIALES EL CONSTRUCTOR S.A. DE C.V.
Sucursal Centro
Cotización #4521

1. M-20384 Cemento Monterrey CPC 40R 50kg    10 bulto   $245.50
2. SKU-VR38 Varilla corrugada 3/8 grado 42 12m    50 pza   $189.00
3. 0045-BLK Block hueco 15x20x40 ligero    500 pza   $12.80
4. CAL-HIDRA Cal hidratada tipo N 25kg    20 bulto   $85.00
5. ARENA-RIO Arena de rio cribada    5 m3   $450.00
TEXT;

echo "\n=== Enviando texto a Gemini AI... ===" . PHP_EOL;

$result = $gemini->structureRawText($testText);

if ($result === null) {
    echo "ERROR: Gemini retornó null (falló o no disponible)" . PHP_EOL;
    echo "Revisa los logs en storage/logs/laravel.log" . PHP_EOL;
} else {
    echo "\n✅ Gemini respondió exitosamente!" . PHP_EOL;
    echo "Proveedor detectado: " . ($result['supplier'] ?? 'N/A') . PHP_EOL;
    echo "Tienda: " . ($result['store'] ?? 'N/A') . PHP_EOL;
    echo "\nProductos extraídos:" . PHP_EOL;
    foreach ($result['items'] as $i => $item) {
        echo "  " . ($i + 1) . ". \"{$item['name']}\" — Cant: {$item['quantity']} {$item['unit']} @ \${$item['unit_price']}" . PHP_EOL;
    }
}
