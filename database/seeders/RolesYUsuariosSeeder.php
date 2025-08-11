<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolesYUsuariosSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles si no existen
        $roles = [
            1 => 'Administrador',
            2 => 'Secretaria',
            3 => 'Tecnico',
            4 => 'Usuario',
        ];

        foreach ($roles as $id => $rol) {
            Role::firstOrCreate(['id' => $id], ['name' => $rol]);
        }

        // Crear usuarios y asignar roles
        $usuarios = [
        
                ['name' => 'Admin Prueba', 'email' => 'admin@cooperativa.test', 'password' => bcrypt('password123'), 'role_id' => 1, 'ci' => '12345678'],
                ['name' => 'Secretaria Prueba', 'email' => 'secretaria@cooperativa.test', 'password' => bcrypt('password123'), 'role_id' => 2, 'ci' => '23456789'],
                ['name' => 'Tecnico Prueba', 'email' => 'tecnico@cooperativa.test', 'password' => bcrypt('password123'), 'role_id' => 3, 'ci' => '34567890'],
                ['name' => 'Usuario Prueba', 'email' => 'usuario@cooperativa.test', 'password' => bcrypt('password123'), 'role_id' => 4, 'ci' => '45678901'],
            ];
          

        foreach ($usuarios as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => $data['password'], 'ci' => $data['ci']]
            );
            
            $user->syncRoles([$data['role_id']]);
        }
    }
}
