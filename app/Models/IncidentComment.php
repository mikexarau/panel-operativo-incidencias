<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $author_name
 * @property string $body
 * @property CarbonInterface $created_at
 */
class IncidentComment extends Model
{
    protected $fillable = [
        'incident_id',
        'author_name',
        'body',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
