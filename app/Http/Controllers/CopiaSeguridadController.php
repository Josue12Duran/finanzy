<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CopiaSeguridadController extends Controller
{
    public function index(): View
    {
        return view('copias-seguridad.index');
    }

    public function download(Request $request, DatabaseBackupService $backups): BinaryFileResponse
    {
        $downloadToken = $request->validate([
            'download_token' => ['required', 'uuid'],
        ])['download_token'];

        $path = $backups->create();
        $deleteImmediately = true;

        try {
            $response = response()
                ->download($path, basename($path), [
                    'Cache-Control' => 'no-store, private',
                    'X-Content-Type-Options' => 'nosniff',
                ])
                ->deleteFileAfterSend();
            $response->headers->setCookie(cookie(
                'backup_download_started',
                $downloadToken,
                1,
                '/',
                null,
                $request->isSecure(),
                false,
                false,
                'lax',
            ));

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
