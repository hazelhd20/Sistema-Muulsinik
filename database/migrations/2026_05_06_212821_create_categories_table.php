<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Insert default categories
        $defaultCategories = [
            'Acero / Herrería',
            'Agregados',
            'Cemento / Concreto',
            'Material Eléctrico',
            'Herramientas',
            'Material Hidráulico',
            'Madera',
            'Pintura',
            'Plomería',
            'Equipo de Seguridad',
            'Otros',
        ];

        foreach ($defaultCategories as $cat) {
            DB::table('categories')->insert([
                'name' => $cat,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            $table->string('category')->nullable();
        });

        Schema::dropIfExists('categories');
    }
};
