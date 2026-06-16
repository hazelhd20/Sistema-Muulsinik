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
            $table->index(['status', 'created_at']);
        });

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->index('tax_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->dropIndex(['tax_source']);
        });
    }
};
