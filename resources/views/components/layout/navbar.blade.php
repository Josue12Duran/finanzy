@props(['user' => null])

@php
    $rolNombre = null;

    if ($user) {
        $rolNombre = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->first()
            : ($user->role ?? null);
    }

    $etiquetasRol = [
        'admin'         => 'Administrador',
        'administrador' => 'Administrador',
        'Administrador' => 'Administrador',
        'empleado'      => 'Empleado',
        'Empleado'      => 'Empleado',
    ];

    $rolEtiqueta = $etiquetasRol[$rolNombre] ?? 'Sin rol';
    $rolColor = $rolEtiqueta === 'Administrador' ? 'primary' : 'success';
@endphp

<nav class="navbar navbar-dark bg-dark px-3 shadow-sm">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ url('/') }}">
        <i class="bi bi-wallet2"></i> Finanzy
    </a>

    <div class="ms-auto d-flex align-items-center gap-3">
        @if ($user)
            <span class="text-white fw-semibold d-none d-md-inline">{{ $user->name }}</span>
            <x-ui.badge :type="$rolColor">{{ $rolEtiqueta }}</x-ui.badge>

            <form action="{{ url('/logout') }}" method="POST" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
            </a>
            <a href="{{ route('register') }}" class="btn btn-light btn-sm">
                <i class="bi bi-person-plus"></i> Registrarse
            </a>
        @endif
    </div>
</nav>
