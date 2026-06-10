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
        // 1. Make project_id nullable in expenses table to allow distributed expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->change();
            // Flag to easily identify if it's an operating/distributed expense
            $table->boolean('is_distributed')->default(false)->after('project_id');
        });

        // 2. Create allocations table for prorated expenses
        Schema::create('expense_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2); // The portion of the expense allocated to this project
            $table->decimal('percentage', 5, 2)->nullable(); // e.g. 25.00 for 25%
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_allocations');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('is_distributed');
            // Reverting to non-nullable might fail if there are nulls, so we're careful.
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }
};
