<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermisosSeeder extends Seeder
{
    public function run()
    {
        // --- LIMPIAR DATOS ANTERIORES (opcional si se vuelve a correr) ---
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- PERMISOS DEL SISTEMA ---
        $permisos = [
            'gestionar usuarios',
            'gestionar conexiones',
            'gestionar lecturas',
            'gestionar facturas',
            'gestionar pagos',
            'gestionar ingresos_egresos',
            'gestionar reportes',
            'ver estado cuenta',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // --- ROLES ---
        $admin = Role::firstOrCreate(['name' => 'Administrador']);
        $secretaria = Role::firstOrCreate(['name' => 'Secretaria']);
        $tecnico = Role::firstOrCreate(['name' => 'Tecnico']);
        $usuario = Role::firstOrCreate(['name' => 'Usuario']);

        // --- ASIGNAR PERMISOS A ROLES ---
        $admin->givePermissionTo(Permission::all());
        $secretaria->givePermissionTo([
            'gestionar usuarios',
            'gestionar conexiones',
            'gestionar facturas',
            'gestionar pagos',
            'gestionar reportes'
        ]);
        $tecnico->givePermissionTo([
            'gestionar lecturas'
        ]);
        $usuario->givePermissionTo([
            'ver estado cuenta'
        ]);
    }
}
