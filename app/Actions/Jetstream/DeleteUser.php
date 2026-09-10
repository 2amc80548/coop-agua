<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        if ($user->hasRole('Administrador')) {
            throw ValidationException::withMessages([
                'password' => ['Por razones de seguridad e integridad del sistema, no se permite eliminar cuentas con rol de Administrador.'],
            ]);
        }

        $user->deleteProfilePhoto();
        $user->tokens->each->delete();
        $user->delete();
    }
}
