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
            $table->string('normalized_name')->nullable()->index()->after('canonical_name');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('normalized_name')->nullable()->index()->after('trade_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('normalized_name');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('normalized_name');
        });
    }
};
