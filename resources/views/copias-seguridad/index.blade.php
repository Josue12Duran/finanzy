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

                    <form id="backup-download-form" method="POST" action="{{ route('copias-seguridad.download') }}" target="backup-download-frame">
                        @csrf
                        <input type="hidden" name="download_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <button type="submit" class="btn btn-primary" data-loading-text="Generando copia…">
                            <i class="bi bi-download me-1" aria-hidden="true"></i>
                            Generar y descargar copia
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <div id="backup-download-error" class="alert alert-danger mt-3" role="alert" hidden>
        No se pudo generar la copia de seguridad. Inténtalo de nuevo.
    </div>

    <div id="backup-loading-overlay" class="backup-loading-overlay" role="dialog" aria-modal="true"
         aria-labelledby="backup-loading-title" aria-describedby="backup-loading-description" tabindex="-1" hidden>
        <div class="bg-white rounded-3 shadow p-4 text-center">
            <div class="spinner-border text-primary mb-3" aria-hidden="true"></div>
            <h2 id="backup-loading-title" class="h5">Generando copia de seguridad</h2>
            <p id="backup-loading-description" class="text-muted mb-0">
                Espera mientras preparamos el archivo. La página seguirá disponible al iniciar la descarga.
            </p>
        </div>
    </div>

    <iframe id="backup-download-frame" name="backup-download-frame" title="Descarga de copia de seguridad" hidden></iframe>
</x-layout.app>
