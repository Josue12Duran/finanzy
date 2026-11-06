<x-layout.auth title="Recuperar contraseña">
    @if (session('status'))
        <x-ui.alert type="success" :message="session('status')" class="mb-3" />
    @endif

    <p class="small text-muted">
        Escribe el correo de tu cuenta y te enviaremos un enlace para restablecer tu contraseña.
    </p>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <x-form.input name="email" type="email" label="Correo electrónico" required autofocus autocomplete="email" />

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-envelope"></i> Enviar enlace
        </button>
    </form>

    <div class="small text-center mt-3">
        <a href="{{ route('login') }}">Volver a iniciar sesión</a>
    </div>
</x-layout.auth>
