<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \DB::enableQueryLog();
    
    $repo = app(\App\Repositories\RequisitionRepository::class);
    
    echo "--- TOTAL DESC ---\n";
    foreach($repo->getPaginatedWithFilters(sortField: 'total', sortDirection: 'desc')->items() as $item) {
        echo "ID: {$item->id} | Total: {$item->cached_total}\n";
    }
    
    print_r(\DB::getQueryLog());

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
