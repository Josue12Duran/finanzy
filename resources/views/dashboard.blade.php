<x-layout.app title="Dashboard" activeRoute="dashboard">
    <x-layout.page-header title="Dashboard" subtitle="Resumen de tu cuenta" />

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h2 class="h5 mb-2">Bienvenido, {{ auth()->user()->name }}</h2>
            <p class="text-muted mb-0">Has iniciado sesión correctamente.</p>
        </div>
    </div>
</x-layout.app>
