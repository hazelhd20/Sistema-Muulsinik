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
        Schema::table('requisition_items', function (Blueprint $table) {
            $columns = ['discount', 'discount_percentage', 'discount_amount'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('requisition_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('requisitions', 'discount')) {
                $table->dropColumn('discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            if (! Schema::hasColumn('requisition_items', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('requisition_items', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('requisition_items', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->nullable();
            }
        });

        Schema::table('requisitions', function (Blueprint $table) {
            if (! Schema::hasColumn('requisitions', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0);
            }
        });
    }
};
