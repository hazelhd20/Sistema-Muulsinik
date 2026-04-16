<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pipeline de Cotizaciones v3 — agrega campos faltantes.
     * Los campos de quotations y documents.requisition_id ya existían
     * de una sesión previa; esta migración solo agrega homologation_status.
     */
    public function up(): void
    {
        // --- RequisitionItems: estado de homologación ---
        if (!Schema::hasColumn('requisition_items', 'homologation_status')) {
            Schema::table('requisition_items', function (Blueprint $table) {
                $table->string('homologation_status')->default('pending')->after('supplier_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('requisition_items', 'homologation_status')) {
            Schema::table('requisition_items', function (Blueprint $table) {
                $table->dropColumn('homologation_status');
            });
        }
    }
};
