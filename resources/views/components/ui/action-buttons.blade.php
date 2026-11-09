{{-- $deleteTarget: id del modal de confirmación de borrado (ej. "modalEliminar-5"). --}}
@props(['editUrl' => null, 'deleteTarget' => null, 'viewUrl' => null])

<div class="d-flex justify-content-end gap-1">
    @if ($viewUrl)
        <a href="{{ $viewUrl }}" class="btn btn-sm btn-outline-secondary" title="Ver detalle" aria-label="Ver detalle">
            <i class="bi bi-eye"></i>
        </a>
    @endif

    @if ($editUrl)
        <a href="{{ $editUrl }}" class="btn btn-sm btn-outline-primary" title="Editar" aria-label="Editar">
            <i class="bi bi-pencil-square"></i>
        </a>
    @endif

    @if ($deleteTarget)
        <button type="button"
                class="btn btn-sm btn-outline-danger"
                data-bs-toggle="modal"
                data-bs-target="#{{ $deleteTarget }}"
                title="Eliminar"
                aria-label="Eliminar">
            <i class="bi bi-trash"></i>
        </button>
    @endif
</div>
