<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Restaurar columna permissions (JSON) en roles
        Schema::table('roles', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('name');
        });

        // Migrar datos desde role_permissions → roles.permissions
        if (Schema::hasTable('role_permissions')) {
            $roles = DB::table('roles')->get();
            foreach ($roles as $role) {
                $perms = DB::table('role_permissions')
                    ->where('role_id', $role->id)
                    ->pluck('permission')
                    ->toArray();

                if (!empty($perms)) {
                    DB::table('roles')
                        ->where('id', $role->id)
                        ->update(['permissions' => json_encode($perms)]);
                }
            }

            Schema::dropIfExists('role_permissions');
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
