<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega columnas para almacenar los totales de línea originales del proveedor.
     *
     * Contexto: El sistema recalculaba subtotal y total por producto a partir
     * de price × qty, lo que introducía variaciones de centavos por redondeo.
     * Al almacenar los valores exactos del proveedor, se preserva la integridad
     * fiscal y se evitan inconsistencias.
     *
     * - line_subtotal: Subtotal de la línea (qty × unit_price, sin IVA) del proveedor.
     * - line_total: Total de la línea (subtotal + IVA) del proveedor.
     * - tax_amount pasa a almacenar IVA TOTAL de línea (no unitario).
     */
    public function up(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            if (!Schema::hasColumn('requisition_items', 'line_subtotal')) {
                $table->decimal('line_subtotal', 14, 2)
                    ->nullable()
                    ->after('tax_source')
                    ->comment('Subtotal de línea del proveedor (qty × P.U. sin IVA)');
            }

            if (!Schema::hasColumn('requisition_items', 'line_total')) {
                $table->decimal('line_total', 14, 2)
                    ->nullable()
                    ->after('line_subtotal')
                    ->comment('Total de línea del proveedor (subtotal + IVA)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            $columns = ['line_subtotal', 'line_total'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('requisition_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
