<x-layout.auth title="Iniciar sesión">
    @if (session('status'))
        <x-ui.alert type="success" :message="session('status')" class="mb-3" />
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <x-form.input name="email" type="email" label="Correo electrónico" required autofocus autocomplete="email" />
        <x-form.input name="password" type="password" label="Contraseña" required autocomplete="current-password" />

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))>
            <label class="form-check-label" for="remember">Recordarme</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
        </button>
    </form>

    <div class="d-flex justify-content-between small mt-3">
        <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
        <a href="{{ route('register') }}">Crear cuenta</a>
    </div>
</x-layout.auth>
