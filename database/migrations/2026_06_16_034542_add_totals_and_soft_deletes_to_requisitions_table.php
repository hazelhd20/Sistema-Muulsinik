<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->decimal('cached_subtotal', 12, 2)->default(0)->after('status');
            $table->decimal('cached_total', 12, 2)->default(0)->after('cached_subtotal');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropColumn(['cached_subtotal', 'cached_total']);
            $table->dropSoftDeletes();
        });
    }
};
