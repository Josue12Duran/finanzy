<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CopiaSeguridadController extends Controller
{
    public function index(): View
    {
        return view('copias-seguridad.index');
    }

    public function download(DatabaseBackupService $backups): BinaryFileResponse
    {
        $path = $backups->create();
        $deleteImmediately = true;

        try {
            $response = response()
                ->download($path, basename($path), [
                    'Cache-Control' => 'no-store, private',
                    'X-Content-Type-Options' => 'nosniff',
                ])
                ->deleteFileAfterSend();

            AuditLogger::log(
                'descargar',
                'copias_seguridad',
                'Se generó y descargó una copia de seguridad de la base de datos.',
            );

            $deleteImmediately = false;

            return $response;
        } finally {
            if ($deleteImmediately && File::exists($path)) {
                File::delete($path);
            }
        }
    }
}
