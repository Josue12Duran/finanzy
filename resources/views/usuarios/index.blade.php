<x-layout.app title="Usuarios" activeRoute="usuarios">
    <x-layout.page-header title="Usuarios" subtitle="Gestión de usuarios del sistema">
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Nuevo usuario
        </a>
    </x-layout.page-header>

    <x-ui.data-table :headers="['Nombre', 'Correo electrónico', 'Rol', 'Estado', 'Acciones']" :is-empty="$usuarios->isEmpty()" empty="No hay usuarios registrados.">
        @foreach ($usuarios as $usuario)
            @php
                $rol = $usuario->roles->first()?->name;
                $esUsuarioActual = $usuario->is(auth()->user());
            @endphp

            <tr>
                <td class="fw-semibold">
                    {{ $usuario->name }}
                    @if ($esUsuarioActual)
                        <span class="badge bg-light text-muted border ms-1">Tú</span>
                    @endif
                </td>
                <td>{{ $usuario->email }}</td>
                <td>
                    <x-ui.badge :type="match ($rol) {
                        'Administrador' => 'primary',
                        'Empleado' => 'info',
                        default => 'secondary',
                    }">
                        {{ $rol ?? 'Sin rol' }}
                    </x-ui.badge>
                </td>
                <td>
                    <x-ui.badge :type="$usuario->activo ? 'success' : 'danger'">
                        {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                    </x-ui.badge>
                </td>
                <td>
                    <div class="d-flex justify-content-end gap-1">
                        <a href="{{ route('usuarios.edit', $usuario) }}"
                           class="btn btn-sm btn-outline-primary"
                           title="Editar" aria-label="Editar">
                            <i class="bi bi-pencil-square"></i>
                        </a>

                        <button type="button"
                                class="btn btn-sm {{ $usuario->activo ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEstado-{{ $usuario->id }}"
                                title="{{ $usuario->activo ? 'Desactivar' : 'Activar' }}"
                                aria-label="{{ $usuario->activo ? 'Desactivar' : 'Activar' }}"
                                @disabled($esUsuarioActual)>
                            <i class="bi {{ $usuario->activo ? 'bi-person-x' : 'bi-person-check' }}"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

    @foreach ($usuarios as $usuario)
        <x-ui.modal
            :id="'modalEstado-'.$usuario->id"
            :title="$usuario->activo ? 'Desactivar usuario' : 'Activar usuario'"
            :action-url="route('usuarios.toggle-status', $usuario)"
            method="PATCH"
            :submit-label="$usuario->activo ? 'Desactivar' : 'Activar'"
            :submit-type="$usuario->activo ? 'danger' : 'success'"
            size="sm">
            @if ($usuario->activo)
                <p class="mb-1">¿Desactivar a <strong>{{ $usuario->name }}</strong> ({{ $usuario->email }})?</p>
                <p class="text-muted small mb-0">No podrá iniciar sesión hasta que sea activado nuevamente.</p>
            @else
                <p class="mb-1">¿Activar a <strong>{{ $usuario->name }}</strong> ({{ $usuario->email }})?</p>
                <p class="text-muted small mb-0">Podrá volver a iniciar sesión con sus credenciales.</p>
            @endif
        </x-ui.modal>
    @endforeach

    @if ($usuarios->hasPages())
        <div class="d-flex justify-content-end mt-3">
            {{ $usuarios->links('pagination::bootstrap-5') }}
        </div>
    @endif
</x-layout.app>
