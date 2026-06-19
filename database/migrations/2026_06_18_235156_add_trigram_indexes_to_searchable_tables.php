<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'pgsql') return;

        // projects
        DB::statement('CREATE INDEX IF NOT EXISTS idx_projects_name_trgm ON projects USING GIN (name gin_trgm_ops);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_projects_client_trgm ON projects USING GIN (client gin_trgm_ops);');

        // expenses
        DB::statement('CREATE INDEX IF NOT EXISTS idx_expenses_concept_trgm ON expenses USING GIN (concept gin_trgm_ops);');

        // suppliers
        DB::statement('CREATE INDEX IF NOT EXISTS idx_suppliers_trade_name_trgm ON suppliers USING GIN (trade_name gin_trgm_ops);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_suppliers_legal_name_trgm ON suppliers USING GIN (legal_name gin_trgm_ops);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_suppliers_rfc_trgm ON suppliers USING GIN (rfc gin_trgm_ops);');

        // products
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_canonical_name_trgm ON products USING GIN (canonical_name gin_trgm_ops);');

        // users
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_name_trgm ON users USING GIN (name gin_trgm_ops);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_email_trgm ON users USING GIN (email gin_trgm_ops);');

        // quick_budgets
        DB::statement('CREATE INDEX IF NOT EXISTS idx_quick_budgets_title_trgm ON quick_budgets USING GIN (title gin_trgm_ops);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'pgsql') return;

        Schema::table('searchable_tables', function (Blueprint $table) {
            //
        });
    }
};
