<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_budgets', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('quick_budget_items', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('quick_budgets', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('quick_budget_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
