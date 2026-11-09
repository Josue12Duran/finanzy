<x-layout.app title="Editar usuario" activeRoute="usuarios">
    <x-layout.page-header title="Editar usuario" :subtitle="$usuario->email" />

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            @include('usuarios._form', [
                'action' => route('usuarios.update', $usuario),
                'method' => 'PUT',
                'submitLabel' => 'Guardar cambios',
            ])
        </div>
    </div>
</x-layout.app>
