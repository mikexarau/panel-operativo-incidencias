<?php

namespace App\Models;

use App\Diagnostics\ConsistencyRule;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $incident_id
 * @property ConsistencyRule $rule
 * @property string $fingerprint
 * @property array<string, mixed> $details
 * @property ?CarbonInterface $resolved_at
 */
class IncidentDataIssue extends Model
{
    protected $fillable = [
        'incident_id',
        'rule',
        'fingerprint',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'rule' => ConsistencyRule::class,
            'details' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
