<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create role_permissions table and migrate data
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('permission');
            $table->unique(['role_id', 'permission']);
        });

        $roles = DB::table('roles')->get();
        $rolePermissionsData = [];
        foreach ($roles as $role) {
            if (! empty($role->permissions)) {
                $perms = json_decode($role->permissions, true);
                if (is_array($perms)) {
                    foreach ($perms as $perm) {
                        $rolePermissionsData[] = [
                            'role_id' => $role->id,
                            'permission' => $perm,
                        ];
                    }
                }
            }
        }
        if (! empty($rolePermissionsData)) {
            DB::table('role_permissions')->insert($rolePermissionsData);
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });

        // 2. Add measure_id to products and requisition_items, drop unit and product_name
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('measure_id')->nullable()->constrained('measures')->nullOnDelete();
        });

        // Migrate product measure data
        $measures = DB::table('measures')->get();
        foreach ($measures as $measure) {
            DB::table('products')
                ->where('unit', $measure->abbreviation)
                ->update(['measure_id' => $measure->id]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->foreignId('measure_id')->nullable()->constrained('measures')->nullOnDelete();
        });

        // Migrate requisition_items measure data
        foreach ($measures as $measure) {
            DB::table('requisition_items')
                ->where('unit', $measure->abbreviation)
                ->update(['measure_id' => $measure->id]);
        }

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'unit']);
        });

        // 3. Rename parsed_data to raw_parsed_data, add is_orphan
        Schema::table('quotations', function (Blueprint $table) {
            $table->renameColumn('parsed_data', 'raw_parsed_data');
            $table->boolean('is_orphan')->default(false)->after('requisition_id');
        });

        // Ensure current orphan quotations have is_orphan = true
        DB::table('quotations')
            ->whereNull('requisition_id')
            ->whereNull('project_id')
            ->update(['is_orphan' => true]);

        // 4. Rename contact_info to notes in suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->renameColumn('contact_info', 'notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->renameColumn('notes', 'contact_info');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('is_orphan');
            $table->renameColumn('raw_parsed_data', 'parsed_data');
        });

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->string('product_name')->nullable();
            $table->string('unit')->nullable();
        });

        $measures = DB::table('measures')->get()->keyBy('id');

        $items = DB::table('requisition_items')->whereNotNull('measure_id')->get();
        foreach ($items as $item) {
            if (isset($measures[$item->measure_id])) {
                DB::table('requisition_items')
                    ->where('id', $item->id)
                    ->update(['unit' => $measures[$item->measure_id]->abbreviation]);
            }
        }

        Schema::table('requisition_items', function (Blueprint $table) {
            $table->dropForeign(['measure_id']);
            $table->dropColumn('measure_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('unit')->nullable();
        });

        $products = DB::table('products')->whereNotNull('measure_id')->get();
        foreach ($products as $product) {
            if (isset($measures[$product->measure_id])) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['unit' => $measures[$product->measure_id]->abbreviation]);
            }
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['measure_id']);
            $table->dropColumn('measure_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->json('permissions')->nullable();
        });

        $roles = DB::table('roles')->get();
        foreach ($roles as $role) {
            $perms = DB::table('role_permissions')->where('role_id', $role->id)->pluck('permission')->toArray();
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($perms)]);
        }

        Schema::dropIfExists('role_permissions');
    }
};
