<x-layout.app title="Usuarios" activeRoute="usuarios">
    <x-layout.page-header title="Usuarios" subtitle="Gestión de usuarios del sistema">
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Nuevo usuario
        </a>
    </x-layout.page-header>

    @php
        $hayFiltros = collect($filtros)->filter(fn ($valor) => filled($valor))->isNotEmpty();
    @endphp

    <form method="GET" action="{{ route('usuarios.index') }}" class="card card-body border-0 shadow-sm mb-4" novalidate>
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-4">
                <label for="q" class="form-label fw-semibold small">Buscar</label>
                <input type="search" id="q" name="q" value="{{ old('q', $filtros['q'] ?? '') }}"
                       placeholder="Nombre o correo electrónico"
                       class="form-control @error('q') is-invalid @enderror">
                @error('q')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="rol" class="form-label fw-semibold small">Rol</label>
                <select id="rol" name="rol" class="form-select @error('rol') is-invalid @enderror">
                    <option value="">Todos los roles</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol }}" @selected(old('rol', $filtros['rol'] ?? '') === $rol)>{{ $rol }}</option>
                    @endforeach
                </select>
                @error('rol')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="estado" class="form-label fw-semibold small">Estado</label>
                <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror">
                    <option value="">Todos los estados</option>
                    <option value="activo" @selected(old('estado', $filtros['estado'] ?? '') === 'activo')>Activo</option>
                    <option value="inactivo" @selected(old('estado', $filtros['estado'] ?? '') === 'inactivo')>Inactivo</option>
                </select>
                @error('estado')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-xl-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros" aria-label="Limpiar filtros">
                    <i class="bi bi-brush"></i>
                </a>
            </div>
        </div>
    </form>

    <x-ui.data-table :headers="['Nombre', 'Correo electrónico', 'Rol', 'Estado', 'Acciones']" :is-empty="$usuarios->isEmpty()" :empty="$hayFiltros ? 'No hay usuarios que coincidan con los filtros.' : 'No hay usuarios registrados.'">
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

    @if ($usuarios->total() > 0)
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
            <span class="small text-muted">
                Mostrando {{ $usuarios->firstItem() }} a {{ $usuarios->lastItem() }} de {{ $usuarios->total() }} usuarios
            </span>
            {{ $usuarios->links('pagination::bootstrap-5') }}
        </div>
    @endif
</x-layout.app>
