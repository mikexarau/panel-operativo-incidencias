<?php

namespace App\Models;

use App\Enums\IncidentLogAction;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Registro de trazabilidad inmutable: no se borra y solo admite marcar voided_at.
 *
 * @property int $id
 * @property int $incident_id
 * @property IncidentLogAction $action
 * @property ?string $old_value
 * @property ?string $new_value
 * @property string $user_name
 * @property ?array<string, mixed> $metadata
 * @property CarbonInterface $created_at
 * @property ?CarbonInterface $voided_at
 * @property Incident $incident
 */
class IncidentLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'incident_id',
        'action',
        'old_value',
        'new_value',
        'user_name',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'action' => IncidentLogAction::class,
            'metadata' => 'array',
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $log) {
            if (array_keys($log->getDirty()) !== ['voided_at']) {
                throw new LogicException('Un log de incidencia es inmutable: solo puede anularse.');
            }
        });

        static::deleting(function () {
            throw new LogicException('Un log de incidencia no puede borrarse: debe anularse.');
        });
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }
}
