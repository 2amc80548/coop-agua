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
        
                ['name' => 'Andres Miranda', 'email' => 'andres@andres.com', 'password' => bcrypt('andres123'), 'role_id' => 1],
                ['name' => 'Secretaria', 'email' => 'secretaria@secretaria.com', 'password' => bcrypt('password123'), 'role_id' => 2],
                ['name' => 'Tecnico', 'email' => 'tecnico@tecnico.com', 'password' => bcrypt('password123'), 'role_id' => 3],
                ['name' => 'Usuario', 'email' => 'usuario@usuario.com', 'password' => bcrypt('password123'), 'role_id' => 4],
            ];
          

        foreach ($usuarios as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => $data['password']]
            );
            
            $user->syncRoles([$data['role_id']]);
        }
    }
}
