<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Intentos fallidos permitidos antes de bloquear temporalmente. */
    private const MAX_INTENTOS = 5;

    /** Minutos (en segundos) que dura el bloqueo tras cada intento fallido. */
    private const SEGUNDOS_BLOQUEO = 60;

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $clave = $this->claveLimite($request);

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            event(new Lockout($request));

            $segundos = RateLimiter::availableIn($clave);

            throw ValidationException::withMessages([
                'email' => [__('auth.throttle', [
                    'seconds' => $segundos,
                    'minutes' => ceil($segundos / 60),
                ])],
            ])->status(429);
        }

        $credenciales = $request->only('email', 'password');

        if (! Auth::attempt($credenciales, $request->boolean('remember'))) {
            RateLimiter::hit($clave, self::SEGUNDOS_BLOQUEO);

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function claveLimite(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
