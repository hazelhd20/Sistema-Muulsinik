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
        Schema::table('quick_budget_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->default(0)->after('quantity');
            $table->decimal('margin_percent', 5, 2)->default(0)->after('unit_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_budget_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'margin_percent']);
        });
    }
};
