<?php

namespace App\Models;

use App\Diagnostics\ConsistencyRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
