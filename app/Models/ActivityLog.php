<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Registro de bitácora / auditoría. Se escribe mediante App\Services\AuditLogger.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $description
 * @property string $module
 * @property string|null $ip_address
 * @property Carbon $created_at
 */
#[Fillable(['user_id', 'action', 'description', 'module', 'ip_address'])]
class ActivityLog extends Model
{
    /**
     * La bitácora es de solo escritura: solo se registra created_at.
     */
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
