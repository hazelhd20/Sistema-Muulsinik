<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Render the view. We need to mock Livewire component rendering or just render the blade component.
    // Actually, simpler: render the page-header directly.
    $html = Blade::render('<x-page-header subtitle="Compras" title="Requisiciones" icon="clipboard-list" />');
    echo "SUCCESS: " . strlen($html) . " bytes\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
