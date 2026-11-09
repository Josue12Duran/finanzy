@props(['activeRoute' => null])

@php
    $modulos = [
        ['ruta' => 'dashboard',  'etiqueta' => 'Panel Financiero',  'icono' => 'bi-speedometer2'],
        ['ruta' => 'ingresos',   'etiqueta' => 'Ingresos',          'icono' => 'bi-arrow-down-circle'],
        ['ruta' => 'gastos',     'etiqueta' => 'Gastos',            'icono' => 'bi-arrow-up-circle'],
        ['ruta' => 'categorias', 'etiqueta' => 'Categorías',        'icono' => 'bi-tags'],
        ['ruta' => 'reportes',   'etiqueta' => 'Reportes',          'icono' => 'bi-file-earmark-bar-graph'],
    ];

    $modulosAdministracion = [
        ['ruta' => 'usuarios', 'etiqueta' => 'Usuarios', 'icono' => 'bi-people'],
        ['ruta' => 'bitacora', 'etiqueta' => 'Bitácora', 'icono' => 'bi-journal-text'],
    ];
@endphp

<aside class="bg-white border-end p-3 flex-shrink-0" style="width: 250px;">
    <nav class="nav flex-column gap-1">
        @foreach ($modulos as $modulo)
            <x-layout.sidebar-link :modulo="$modulo" :active-route="$activeRoute" />
        @endforeach

        @role('Administrador')
            <hr class="my-2">
            <span class="text-uppercase text-muted small fw-semibold px-2 mb-1">Administración</span>

            @foreach ($modulosAdministracion as $modulo)
                <x-layout.sidebar-link :modulo="$modulo" :active-route="$activeRoute" />
            @endforeach
        @endrole
    </nav>
</aside>
