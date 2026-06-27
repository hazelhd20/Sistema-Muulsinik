<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra roles predefinidos y un usuario administrador inicial.
     */
    public function run(): void
    {
        // Roles del sistema — ERS v2.0 §2.3 (RF-AUTH-02)
        // Reducido de 5 a 3 roles funcionales para constructora pequeña.
        $roles = [
            [
                'name' => 'Administrador',
                'permissions' => ['*'],
            ],
            [
                'name' => 'Encargado de Compras',
                'permissions' => [
                    'requisiciones.ver', 'requisiciones.crear', 'requisiciones.editar', 'requisiciones.eliminar', 'requisiciones.aprobar',
                    'proveedores.ver', 'proveedores.crear', 'proveedores.editar', 'proveedores.eliminar',
                    'cotizaciones.ver', 'cotizaciones.crear', 'cotizaciones.editar', 'cotizaciones.eliminar',
                    'productos.ver', 'productos.crear', 'productos.eliminar',
                    'catalogos.ver', 'catalogos.editar',
                    'reportes.ver',
                    'gastos.ver',
                    'proyectos.ver',
                ],
            ],
            [
                'name' => 'Supervisor / Operativo',
                'permissions' => [
                    'proyectos.ver',
                    'gastos.ver', 'gastos.crear',
                    'requisiciones.ver', 'requisiciones.crear', 'requisiciones.editar',
                    'cotizaciones.ver', 'cotizaciones.crear',
                    'reportes.ver',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                ['permissions' => $roleData['permissions']]
            );
        }

        // Usuario administrador inicial
        $adminRole = Role::where('name', 'Administrador')->first();

        User::updateOrCreate(
            ['email' => 'admin@muulsinik.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'active' => true,
            ]
        );

        // Configuraciones por defecto
        $this->call(SettingsSeeder::class);
    }
}
