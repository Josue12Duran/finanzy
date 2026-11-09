<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltrarBitacoraRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Consulta de la bitácora del sistema. Acceso restringido al rol Administrador
 * mediante el middleware de la ruta.
 */
class ActivityLogController extends Controller
{
    public const POR_PAGINA = 15;

    public function index(FiltrarBitacoraRequest $request): View
    {
        $filtros = $request->validated();

        $registros = ActivityLog::query()
            ->with('user:id,name,email')
            ->when(
                $filtros['user_id'] ?? null,
                fn (Builder $consulta, int|string $userId) => $consulta->where('user_id', $userId),
            )
            ->when(
                $filtros['fecha_inicio'] ?? null,
                fn (Builder $consulta, string $inicio) => $consulta->where('created_at', '>=', Carbon::parse($inicio)->startOfDay()),
            )
            ->when(
                $filtros['fecha_fin'] ?? null,
                fn (Builder $consulta, string $fin) => $consulta->where('created_at', '<=', Carbon::parse($fin)->endOfDay()),
            )
            ->when(
                $filtros['modulo'] ?? null,
                fn (Builder $consulta, string $modulo) => $consulta->where('module', $modulo),
            )
            ->latest('created_at')
            ->latest('id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('bitacora.index', [
            'registros' => $registros,
            'filtros' => $filtros,
            'usuarios' => User::query()->orderBy('name')->pluck('name', 'id'),
            'modulos' => ActivityLog::query()->select('module')->distinct()->orderBy('module')->pluck('module'),
        ]);
    }
}
