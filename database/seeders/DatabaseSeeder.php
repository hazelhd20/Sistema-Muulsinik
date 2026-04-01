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
        // Roles del sistema (RF-AUTH-02)
        $roles = [
            [
                'name' => 'Administrador',
                'permissions' => ['*'],
            ],
            [
                'name' => 'Gerente de Proyecto',
                'permissions' => [
                    'proyectos.ver', 'proyectos.editar',
                    'gastos.ver', 'gastos.crear',
                    'requisiciones.ver', 'requisiciones.aprobar',
                    'reportes.ver', 'documentos.ver',
                ],
            ],
            [
                'name' => 'Comprador',
                'permissions' => [
                    'requisiciones.ver', 'requisiciones.crear', 'requisiciones.editar',
                    'proveedores.ver', 'proveedores.crear', 'proveedores.editar',
                    'cotizaciones.cargar', 'productos.ver',
                ],
            ],
            [
                'name' => 'Supervisor de Campo',
                'permissions' => [
                    'proyectos.ver',
                    'gastos.ver', 'gastos.crear',
                    'requisiciones.ver', 'requisiciones.crear',
                    'documentos.ver',
                ],
            ],
            [
                'name' => 'Contador',
                'permissions' => [
                    'proyectos.ver',
                    'gastos.ver',
                    'reportes.ver',
                    'presupuestos.ver',
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
    }
}
