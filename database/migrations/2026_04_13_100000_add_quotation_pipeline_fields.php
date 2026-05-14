<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pipeline de Cotizaciones v3 — agrega campos faltantes.
     */
    public function up(): void
    {
        // --- Quotations: campos del pipeline de procesamiento ---
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('requisition_id')->constrained()->nullOnDelete();
            $table->string('original_filename')->nullable()->after('file_type');
            $table->string('status')->default('pending')->after('original_filename');
            $table->text('raw_text')->nullable()->after('status');
            $table->json('parsed_data')->nullable()->after('raw_text');
            $table->text('error_message')->nullable()->after('parsed_data');
            $table->foreignId('uploaded_by')->nullable()->after('error_message')->constrained('users')->nullOnDelete();
        });

        // --- RequisitionItems: estado de homologación ---
        if (!Schema::hasColumn('requisition_items', 'homologation_status')) {
            Schema::table('requisition_items', function (Blueprint $table) {
                $table->string('homologation_status')->default('pending')->after('supplier_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn(['project_id', 'original_filename', 'status', 'raw_text', 'parsed_data', 'error_message', 'uploaded_by']);
        });

        if (Schema::hasColumn('requisition_items', 'homologation_status')) {
            Schema::table('requisition_items', function (Blueprint $table) {
                $table->dropColumn('homologation_status');
            });
        }
    }
};
