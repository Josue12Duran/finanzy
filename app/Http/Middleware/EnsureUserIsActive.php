<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de un usuario que fue desactivado mientras tenía una sesión abierta.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        if ($usuario && ! $usuario->activo) {
            Auth::guard()->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está desactivada. Contacta a un administrador.',
            ]);
        }

        return $next($request);
    }
}
