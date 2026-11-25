@props(['activeRoute' => null])

@php
    /*
     * clave: prefijo de la URL que marca el módulo como activo.
     * ruta:  nombre de la ruta de Laravel. Si todavía no está registrada, el módulo
     *        se muestra como "Próximamente" y se activa solo cuando la ruta exista.
     */
    $modulos = [
        ['clave' => 'dashboard',  'ruta' => 'dashboard',        'etiqueta' => 'Panel Financiero', 'icono' => 'bi-speedometer2'],
        ['clave' => 'ingresos',   'ruta' => 'ingresos.index',   'etiqueta' => 'Ingresos',         'icono' => 'bi-arrow-down-circle'],
        ['clave' => 'gastos',     'ruta' => 'gastos.index',     'etiqueta' => 'Gastos',           'icono' => 'bi-arrow-up-circle'],
        ['clave' => 'categorias', 'ruta' => 'categorias.index', 'etiqueta' => 'Categorías',       'icono' => 'bi-tags'],
        ['clave' => 'reportes',   'ruta' => 'reportes.index',   'etiqueta' => 'Reportes',         'icono' => 'bi-file-earmark-bar-graph'],
    ];

    $modulosAdministracion = [
        ['clave' => 'usuarios', 'ruta' => 'usuarios.index', 'etiqueta' => 'Usuarios', 'icono' => 'bi-people'],
        ['clave' => 'bitacora', 'ruta' => 'bitacora.index', 'etiqueta' => 'Bitácora', 'icono' => 'bi-journal-text'],
        ['clave' => 'copias-seguridad', 'ruta' => 'copias-seguridad.index', 'etiqueta' => 'Copias de seguridad', 'icono' => 'bi-database'],
    ];
@endphp

{{-- Escritorio (lg o más): columna fija. Móvil: offcanvas que abre el botón de menú de la navbar. --}}
<div class="app-sidebar">
    <aside class="offcanvas-lg offcanvas-start" id="sidebarMenu" tabindex="-1" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header border-bottom">
            <h2 class="offcanvas-title h5 fw-bold d-flex align-items-center gap-2 mb-0" id="sidebarMenuLabel">
                <i class="bi bi-wallet2" aria-hidden="true"></i> Finanzy
            </h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Cerrar menú"></button>
        </div>

        <div class="offcanvas-body">
            <nav class="nav flex-column gap-1 w-100 p-lg-3" aria-label="Menú principal">
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
        </div>
    </aside>
</div>
