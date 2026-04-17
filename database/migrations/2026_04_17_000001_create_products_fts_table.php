<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Crea una tabla virtual FTS5 para búsqueda de texto completo
 * sobre el catálogo de productos y sus aliases.
 *
 * FTS5 permite búsquedas tokenizadas ultra-rápidas en SQLite,
 * donde "cemento gris" coincide con "Cemento Portland Gris 50kg"
 * sin importar el orden de las palabras.
 *
 * Esta tabla es un índice complementario; los datos canónicos
 * permanecen en 'products' y 'product_aliases'.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Solo crear FTS5 si estamos en SQLite
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

            // Poblar el índice con los productos existentes
            $this->rebuildIndex();
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TABLE IF EXISTS products_fts');
        }
    }

    /**
     * Reconstruye el índice FTS5 a partir de los datos actuales.
     */
    private function rebuildIndex(): void
    {
        $products = DB::table('products')->get();

        foreach ($products as $product) {
            $aliases = DB::table('product_aliases')
                ->where('product_id', $product->id)
                ->pluck('alias_name')
                ->implode(' | ');

            DB::table('products_fts')->insert([
                'product_id'     => $product->id,
                'canonical_name' => $product->canonical_name,
                'aliases'        => $aliases,
                'category'       => $product->category ?? '',
            ]);
        }
    }
};
