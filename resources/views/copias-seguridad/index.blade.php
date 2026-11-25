<x-layout.app title="Copias de seguridad" activeRoute="copias-seguridad">
    <x-layout.page-header title="Copias de seguridad" subtitle="Respaldo de la base de datos" />

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-database fs-3 text-primary" aria-hidden="true"></i>
                <div>
                    <h2 class="h5 fw-bold">Generar respaldo de la base de datos</h2>
                    <p class="text-muted mb-4">
                        El respaldo incluye la estructura y los datos. Se generará en el servidor y se descargará
                        directamente en este navegador; el archivo temporal se elimina al terminar la descarga.
                    </p>

                    <form method="POST" action="{{ route('copias-seguridad.download') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-download me-1" aria-hidden="true"></i>
                            Generar y descargar copia
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layout.app>
