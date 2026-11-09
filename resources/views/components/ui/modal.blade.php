{{--
    $id:          id único del modal (requerido para data-bs-target).
    $actionUrl:   si se define, el cuerpo se envuelve en un <form> POST con @csrf.
    $method:      método HTTP para el formulario (PUT/PATCH/DELETE usan @method).
    $submitLabel: texto del botón principal.
    $size:        '', 'sm', 'lg', 'xl'.
    $footer:      slot opcional que reemplaza los botones por defecto.
--}}
@props([
    'id',
    'title',
    'actionUrl' => null,
    'method' => 'POST',
    'submitLabel' => 'Guardar',
    'submitType' => 'primary',
    'size' => '',
])

@php
    $tamano = $size ? "modal-{$size}" : '';
    $metodoHttp = strtoupper($method);
    $usaFormulario = $actionUrl !== null;
@endphp

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $tamano }}">
        <div class="modal-content border-0 shadow">
            @if ($usaFormulario)
                <form action="{{ $actionUrl }}" method="{{ in_array($metodoHttp, ['GET', 'POST']) ? $metodoHttp : 'POST' }}">
                    @csrf
                    @if (! in_array($metodoHttp, ['GET', 'POST']))
                        @method($metodoHttp)
                    @endif
            @endif

                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="{{ $id }}-label">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    {{ $slot }}
                </div>

                <div class="modal-footer">
                    @if (isset($footer))
                        {{ $footer }}
                    @else
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        @if ($usaFormulario)
                            <button type="submit" class="btn btn-{{ $submitType }}">{{ $submitLabel }}</button>
                        @endif
                    @endif
                </div>

            @if ($usaFormulario)
                </form>
            @endif
        </div>
    </div>
</div>
