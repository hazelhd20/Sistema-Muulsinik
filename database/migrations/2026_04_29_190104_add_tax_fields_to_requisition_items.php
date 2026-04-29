<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega campos fiscales para el manejo de IVA en cotizaciones.
     *
     * - unit_price_original: valor exacto de la cotización (trazabilidad).
     * - tax_amount: IVA por unidad (del proveedor o calculado).
     * - tax_source: origen del dato fiscal para auditoría.
     *
     * Nota: unit_price existente pasa a almacenar siempre precio SIN IVA.
     */
    public function up(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            if (!Schema::hasColumn('requisition_items', 'unit_price_original')) {
                $table->decimal('unit_price_original', 14, 2)
                    ->nullable()
                    ->after('unit_price')
                    ->comment('Precio exacto de la cotización (trazabilidad)');
            }

            if (!Schema::hasColumn('requisition_items', 'tax_amount')) {
                $table->decimal('tax_amount', 14, 2)
                    ->nullable()
                    ->after('unit_price_original')
                    ->comment('IVA por unidad (del proveedor o calculado)');
            }

            if (!Schema::hasColumn('requisition_items', 'tax_source')) {
                $table->string('tax_source', 30)
                    ->nullable()
                    ->after('tax_amount')
                    ->comment('Origen: supplier_per_item|supplier_global|user_confirmed|calculated');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            $columns = ['unit_price_original', 'tax_amount', 'tax_source'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('requisition_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
