<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina la columna homologation_status de requisition_items.
 *
 * Decisión de diseño: La homologación se reemplaza por normalización
 * canónica automática (DataNormalizerService). El catálogo products
 * se mantiene como referencia opcional, pero ya no bloquea
 * el flujo de aprobación de requisiciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            if (Schema::hasColumn('requisition_items', 'homologation_status')) {
                $table->dropColumn('homologation_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requisition_items', function (Blueprint $table) {
            if (!Schema::hasColumn('requisition_items', 'homologation_status')) {
                $table->string('homologation_status')->default('pending')->after('supplier_id');
            }
        });
    }
};
