<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina estructuras huérfanas confirmadas:
 *
 * - product_aliases: Tabla scaffoldeada para homologación de productos (RF-REQ-05).
 *   El diseño evolucionó a usar `products.canonical_name` + `products.normalized_name`
 *   como fuente de verdad única. No existe modelo ProductAlias, ni consulta
 *   a esta tabla en ningún servicio, Livewire o vista del sistema.
 *
 * - documents: Tabla creada para gestión documental (RF-DOC). El módulo nunca
 *   se implementó: sin ruta activa, sin modelo Eloquent, sin vistas. Los archivos
 *   se gestionan directamente con rutas almacenadas en cada entidad
 *   (quotations.file_path, expenses.receipt_file).
 *
 * - users.avatar: Columna añadida en migración 2026_05_18 pero dejada vacía
 *   (el cuerpo del up() no tiene código). No está en $fillable del modelo User,
 *   ni referenciada en ningún blade o componente Livewire.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de alias de productos — sin modelo ni consumidor en el codebase
        Schema::dropIfExists('product_aliases');

        // 2. Tabla de documentos — módulo RF-DOC nunca implementado
        Schema::dropIfExists('documents');

        // 3. Columna avatar en users — migración stub vacía, columna nunca usada
        if (Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('avatar');
            });
        }
    }

    public function down(): void
    {
        // Restaurar product_aliases (estructura original de la migración 2026_03_28_000005)
        Schema::create('product_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('alias_name');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Restaurar documents (estructura original de la migración 2026_03_28_000003)
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->default('otros');
            $table->string('file_path');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requisition_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Restaurar columna avatar
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('active');
        });
    }
};
