<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina columnas huérfanas/legacy que ocupan espacio sin ser leídas ni escritas.
 *
 * quotations:
 * - processing_status: Reemplazada por 'status'. Sin referencias en el codebase.
 * - extracted_data:    Reemplazada por 'parsed_data'. Sin referencias en el codebase.
 * - processing_error:  Reemplazada por 'error_message'. Sin referencias en el codebase.
 *
 * requisitions:
 * - need_date: Solo se asigna como null en todo el código. Sin uso en vistas ni reportes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- quotations: columnas legacy del pipeline v1 ---
        Schema::table('quotations', function (Blueprint $table) {
            $columns = ['processing_status', 'extracted_data', 'processing_error'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('quotations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // --- requisitions: campo que nunca se usó ---
        Schema::table('requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('requisitions', 'need_date')) {
                $table->dropColumn('need_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'processing_status')) {
                $table->string('processing_status')->nullable();
            }
            if (!Schema::hasColumn('quotations', 'extracted_data')) {
                $table->json('extracted_data')->nullable();
            }
            if (!Schema::hasColumn('quotations', 'processing_error')) {
                $table->text('processing_error')->nullable();
            }
        });

        Schema::table('requisitions', function (Blueprint $table) {
            if (!Schema::hasColumn('requisitions', 'need_date')) {
                $table->date('need_date')->nullable();
            }
        });
    }
};
