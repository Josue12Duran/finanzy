<x-layout.auth title="Restablecer contraseña">
    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <x-form.input name="email" type="email" label="Correo electrónico" :value="$email" required autofocus autocomplete="email" />
        <x-form.input name="password" type="password" label="Nueva contraseña" required autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="Confirmar nueva contraseña" required autocomplete="new-password" />

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-key"></i> Restablecer contraseña
        </button>
    </form>
</x-layout.auth>
