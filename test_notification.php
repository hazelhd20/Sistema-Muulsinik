<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
if ($user) {
    echo "Sending notification to user {$user->id}...\n";
    try {
        $quotation = \App\Models\Quotation::first();
        if ($quotation) {
            $user->notify(new \App\Notifications\QuotationProcessed($quotation, true));
            echo "Notification sent.\n";
            echo "Notifications count: " . \DB::table('notifications')->count() . "\n";
        } else {
            echo "No quotation found to test with.\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No users found.\n";
}
