<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('client')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('margin_percent', 5, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->string('status')->default('borrador');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('quick_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quick_budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('concept');
            $table->foreignId('measure_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_budget_items');
        Schema::dropIfExists('quick_budgets');
    }
};
