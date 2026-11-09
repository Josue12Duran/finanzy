<x-layout.app title="Nuevo usuario" activeRoute="usuarios">
    <x-layout.page-header title="Nuevo usuario" subtitle="Registrar una cuenta y asignarle un rol" />

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            @include('usuarios._form', [
                'action' => route('usuarios.store'),
                'method' => 'POST',
                'submitLabel' => 'Crear usuario',
            ])
        </div>
    </div>
</x-layout.app>
