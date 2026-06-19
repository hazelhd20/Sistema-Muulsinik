<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'pgsql') return;

        // Requisition Items
        if (Schema::hasTable('requisition_items')) {
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_requisition_items_requisition_id ON requisition_items(requisition_id);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_requisition_items_product_id ON requisition_items(product_id);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_requisition_items_supplier_id ON requisition_items(supplier_id);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_requisition_items_measure_id ON requisition_items(measure_id);');
        }

        // Requisitions
        if (Schema::hasTable('requisitions')) {
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_requisitions_created_by ON requisitions(created_by);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_requisitions_approved_by ON requisitions(approved_by);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_requisitions_vendor_id ON requisitions(vendor_id);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_requisitions_status ON requisitions(status);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_requisitions_date ON requisitions(date);');
        }

        // Expenses
        if (Schema::hasTable('expenses')) {
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_expenses_date ON expenses(date);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_expenses_user_id ON expenses(user_id);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_expenses_project_id ON expenses(project_id);');
        }

        // Purchase Orders
        if (Schema::hasTable('purchase_orders')) {
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_purchase_orders_requisition_id ON purchase_orders(requisition_id);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_purchase_orders_supplier_id ON purchase_orders(supplier_id);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_purchase_orders_project_id ON purchase_orders(project_id);');
        }

        // Quotations
        if (Schema::hasTable('quotations')) {
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_quotations_requisition_id ON quotations(requisition_id);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_quotations_supplier_id ON quotations(supplier_id);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_quotations_project_id ON quotations(project_id);');
        }

        // Expense Allocations
        if (Schema::hasTable('expense_allocations')) {
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_expense_allocations_expense_id ON expense_allocations(expense_id);');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_expense_allocations_project_id ON expense_allocations(project_id);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'pgsql') return;

        if (Schema::hasTable('requisition_items')) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_requisition_items_requisition_id;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_requisition_items_product_id;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_requisition_items_supplier_id;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_requisition_items_measure_id;');
        }
        
        if (Schema::hasTable('requisitions')) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_requisitions_created_by;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_requisitions_approved_by;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_requisitions_vendor_id;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_requisitions_status;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_requisitions_date;');
        }

        if (Schema::hasTable('expenses')) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_expenses_date;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_expenses_user_id;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_expenses_project_id;');
        }

        if (Schema::hasTable('purchase_orders')) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_purchase_orders_requisition_id;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_purchase_orders_supplier_id;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_purchase_orders_project_id;');
        }

        if (Schema::hasTable('quotations')) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_quotations_requisition_id;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_quotations_supplier_id;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_quotations_project_id;');
        }

        if (Schema::hasTable('expense_allocations')) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_expense_allocations_expense_id;');
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_expense_allocations_project_id;');
        }
    }
};
