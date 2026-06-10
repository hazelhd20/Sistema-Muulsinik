<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega campo de descuento por partida a requisition_items.
     *
     * Solo se almacena el porcentaje de descuento (dato fuente).
     * Los montos derivados (discount_amount, line_discount_total)
     * se calculan via accessors en el modelo para evitar
     * redundancia e inconsistencia de datos.
     *
     * El precio neto post-descuento ya se almacena en unit_price.
     * El precio bruto original ya se almacena en unit_price_original.
     */
    public function up(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            if (! Schema::hasColumn('requisition_items', 'discount_percent')) {
                $table->decimal('discount_percent', 8, 4)
                    ->nullable()
                    ->after('unit_price_original')
                    ->comment('Porcentaje de descuento aplicado (dato fuente o calculado)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            if (Schema::hasColumn('requisition_items', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
        });
    }
};
