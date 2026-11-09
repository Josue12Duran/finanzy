<x-layout.auth title="Crear cuenta">
    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <x-form.input name="name" label="Nombre" required autofocus autocomplete="name" />
        <x-form.input name="email" type="email" label="Correo electrónico" required autocomplete="email" />
        <x-form.input name="password" type="password" label="Contraseña" required autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="Confirmar contraseña" required autocomplete="new-password" />

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-person-plus"></i> Registrarse
        </button>
    </form>

    <div class="small text-center mt-3">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
    </div>
</x-layout.auth>
