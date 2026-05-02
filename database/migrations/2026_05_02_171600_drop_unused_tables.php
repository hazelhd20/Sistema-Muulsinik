<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina tablas que fueron scaffoldeadas pero nunca se implementaron:
 *
 * - purchase_orders: Sin UI, sin ruta, sin funcionalidad real (RF-PROV-04 pendiente).
 * - audit_logs:      Sin modelo Eloquent, sin escritura/lectura en toda la app.
 * - products_fts:    Índice FTS5 huérfano, nunca consultado (búsquedas usan LIKE).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('audit_logs');

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TABLE IF EXISTS products_fts');
        }
    }

    public function down(): void
    {
        Schema::create('purchase_orders', function ($table) {
            $table->id();
            $table->foreignId('requisition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->decimal('total', 14, 2)->default(0);
            $table->enum('status', ['pendiente', 'enviada', 'recibida', 'cancelada'])->default('pendiente');
            $table->date('date')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('
                CREATE VIRTUAL TABLE IF NOT EXISTS products_fts USING fts5(
                    product_id UNINDEXED,
                    canonical_name,
                    aliases,
                    category,
                    tokenize="unicode61 remove_diacritics 2"
                )
            ');
        }
    }
};
