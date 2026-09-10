<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {--name= : Nombre del administrador} {--email= : Correo electrónico} {--password= : Contraseña}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea o restablece un usuario con rol de Administrador en el sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name') ?: $this->ask('Nombre del Administrador', 'Administrador General');
        $email = $this->option('email') ?: $this->ask('Correo electrónico', 'admin@admin.com');
        $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 8 caracteres)');

        if (!$password) {
            $password = 'admin12345';
            $this->info("No se ingresó contraseña. Se usará la contraseña por defecto: admin12345");
        }

        // Asegurar que el rol Administrador exista
        $adminRole = Role::firstOrCreate(['id' => 1, 'name' => 'Administrador']);

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->name = $name;
            $user->password = Hash::make($password);
            $user->is_active = true;
            $user->save();
            $this->info("El usuario con correo '{$email}' ya existía. Se han actualizado sus credenciales y restablecido su acceso.");
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_active' => true,
            ]);
            $this->info("¡Usuario administrador creado exitosamente!");
        }

        $user->syncRoles([$adminRole->id]);

        $this->table(
            ['ID', 'Nombre', 'Email', 'Rol', 'Estado'],
            [[$user->id, $user->name, $user->email, 'Administrador', 'Activo']]
        );

        return Command::SUCCESS;
    }
}
