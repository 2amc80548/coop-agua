<?php

namespace App\Http\Responses;

// ¡Usamos los contratos de Fortify!
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Illuminate\Http\RedirectResponse;

class CustomRegisterResponse implements RegisterResponseContract
{
    /**
     * Crea la respuesta HTTP.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): RedirectResponse
    {
        // ¡Esta es tu lógica!
        // En lugar de redirigir al dashboard, lo devolvemos al login
        // con el mensaje flash "status" que pediste.
        
        return redirect()->route('login')->with('status', 
            '¡Registro Exitoso! Su cuenta ha sido creada. Por favor, pase por nuestras oficinas para habilitar su acceso y vincularlo a su cuenta de afiliado.'
        );
    }
}