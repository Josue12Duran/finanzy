<x-layout.app title="Bitácora" activeRoute="bitacora">
    <x-layout.page-header title="Bitácora" subtitle="Registro de actividad del sistema" />

    <form method="GET" action="{{ route('bitacora.index') }}" class="card card-body border-0 shadow-sm mb-4" novalidate>
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-3">
                <label for="user_id" class="form-label fw-semibold small">Usuario</label>
                <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                    <option value="">Todos los usuarios</option>
                    @foreach ($usuarios as $id => $nombre)
                        <option value="{{ $id }}" @selected((string) old('user_id', $filtros['user_id'] ?? '') === (string) $id)>{{ $nombre }}</option>
                    @endforeach
                </select>
                @error('user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="fecha_inicio" class="form-label fw-semibold small">Desde</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio"
                       value="{{ old('fecha_inicio', $filtros['fecha_inicio'] ?? '') }}"
                       class="form-control @error('fecha_inicio') is-invalid @enderror">
                @error('fecha_inicio')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="fecha_fin" class="form-label fw-semibold small">Hasta</label>
                <input type="date" id="fecha_fin" name="fecha_fin"
                       value="{{ old('fecha_fin', $filtros['fecha_fin'] ?? '') }}"
                       class="form-control @error('fecha_fin') is-invalid @enderror">
                @error('fecha_fin')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6 col-xl-2">
                <label for="modulo" class="form-label fw-semibold small">Módulo</label>
                <select id="modulo" name="modulo" class="form-select @error('modulo') is-invalid @enderror">
                    <option value="">Todos los módulos</option>
                    @foreach ($modulos as $modulo)
                        <option value="{{ $modulo }}" @selected(old('modulo', $filtros['modulo'] ?? '') === $modulo)>{{ ucfirst($modulo) }}</option>
                    @endforeach
                </select>
                @error('modulo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-xl-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="{{ route('bitacora.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros" aria-label="Limpiar filtros">
                    <i class="bi bi-eraser"></i>
                </a>
            </div>
        </div>
    </form>

    <x-ui.data-table :headers="['Fecha y hora', 'Usuario', 'Módulo', 'Acción', 'Descripción', 'IP']" :is-empty="$registros->isEmpty()" empty="No hay registros de bitácora que coincidan con los filtros.">
        @foreach ($registros as $registro)
            @php
                $tipoAccion = match ($registro->action) {
                    'crear', 'activar' => 'success',
                    'editar' => 'info',
                    'desactivar', 'eliminar' => 'danger',
                    default => 'secondary',
                };
            @endphp

            <tr>
                <td class="text-nowrap small">{{ $registro->created_at->format('d/m/Y H:i:s') }}</td>
                <td>
                    @if ($registro->user)
                        <div class="fw-semibold">{{ $registro->user->name }}</div>
                        <div class="small text-muted">{{ $registro->user->email }}</div>
                    @else
                        <span class="text-muted fst-italic">Sin usuario</span>
                    @endif
                </td>
                <td>
                    <x-ui.badge type="dark">{{ ucfirst($registro->module) }}</x-ui.badge>
                </td>
                <td>
                    <x-ui.badge :type="$tipoAccion">{{ ucfirst($registro->action) }}</x-ui.badge>
                </td>
                <td class="small text-break">{{ $registro->description ?? '—' }}</td>
                <td class="small font-monospace text-nowrap">{{ $registro->ip_address ?? '—' }}</td>
            </tr>
        @endforeach
    </x-ui.data-table>

    @if ($registros->total() > 0)
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
            <span class="small text-muted">
                Mostrando {{ $registros->firstItem() }} a {{ $registros->lastItem() }} de {{ $registros->total() }} registros
            </span>
            {{ $registros->links('pagination::bootstrap-5') }}
        </div>
    @endif
</x-layout.app>
