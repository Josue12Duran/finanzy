<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Servicio centralizado de bitácora / auditoría.
 *
 * Uso desde cualquier controlador, servicio o comando:
 *
 *     use App\Services\AuditLogger;
 *
 *     AuditLogger::log('crear', 'usuarios', 'Creó el usuario juan@correo.com');
 *
 * El ID del usuario en sesión y la IP del cliente se capturan automáticamente.
 * Si no hay usuario autenticado (p. ej. intento de login fallido o un comando
 * de consola), user_id queda en null, salvo que se pase $userId explícitamente.
 */
class AuditLogger
{
    /**
     * Registra un evento en la tabla activity_logs.
     *
     * @param  string  $action  Acción realizada (ej. "crear", "editar", "eliminar", "login").
     * @param  string  $module  Módulo de origen (ej. "usuarios", "auth", "demo").
     * @param  string|null  $description  Detalle legible del evento.
     * @param  int|null  $userId  Usuario responsable. Por defecto, el usuario en sesión.
     */
    public static function log(
        string $action,
        string $module,
        ?string $description = null,
        ?int $userId = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => Str::limit($action, 100, ''),
            'description' => $description,
            'module' => Str::limit($module, 100, ''),
            'ip_address' => request()->ip(),
        ]);
    }
}
