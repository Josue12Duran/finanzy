@props(['activeRoute' => null])

@php
    $modulos = [
        ['ruta' => 'dashboard',  'etiqueta' => 'Panel Financiero',  'icono' => 'bi-speedometer2'],
        ['ruta' => 'ingresos',   'etiqueta' => 'Ingresos',          'icono' => 'bi-arrow-down-circle'],
        ['ruta' => 'gastos',     'etiqueta' => 'Gastos',            'icono' => 'bi-arrow-up-circle'],
        ['ruta' => 'categorias', 'etiqueta' => 'Categorías',        'icono' => 'bi-tags'],
        ['ruta' => 'usuarios',   'etiqueta' => 'Usuarios',          'icono' => 'bi-people'],
        ['ruta' => 'bitacora',   'etiqueta' => 'Bitácora',          'icono' => 'bi-journal-text'],
        ['ruta' => 'reportes',   'etiqueta' => 'Reportes',          'icono' => 'bi-file-earmark-bar-graph'],
    ];
@endphp

<aside class="bg-white border-end p-3 flex-shrink-0" style="width: 250px;">
    <nav class="nav flex-column gap-1">
        @foreach ($modulos as $modulo)
            @php
                $activo = $activeRoute !== null
                    && str_starts_with(ltrim($activeRoute, '/'), $modulo['ruta']);
            @endphp

            <a href="{{ url('/' . $modulo['ruta']) }}"
               @class(['sidebar-link', 'is-active' => $activo])
               @if ($activo) aria-current="page" @endif>
                <i class="bi {{ $modulo['icono'] }} sidebar-link__icon" aria-hidden="true"></i>
                <span>{{ $modulo['etiqueta'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
