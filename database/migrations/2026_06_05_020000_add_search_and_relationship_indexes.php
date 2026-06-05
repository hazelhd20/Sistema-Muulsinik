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
        Schema::table('products', function (Blueprint $table) {
            $table->index('canonical_name');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('concept');
            $table->index('project_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('requisitions', function (Blueprint $table) {
            $table->index('number');
            $table->index('project_id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('trade_name');
        });

        Schema::table('quick_budgets', function (Blueprint $table) {
            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_budgets', function (Blueprint $table) {
            $table->dropIndex(['title']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['trade_name']);
        });

        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropIndex(['number']);
            $table->dropIndex(['project_id']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['concept']);
            $table->dropIndex(['project_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['canonical_name']);
        });
    }
};
