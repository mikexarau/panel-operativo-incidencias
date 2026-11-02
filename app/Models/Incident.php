<?php

namespace App\Models;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'requester_name',
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
            'priority' => IncidentPriority::class,
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(IncidentLog::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class);
    }

    public function dataIssues(): HasMany
    {
        return $this->hasMany(IncidentDataIssue::class);
    }

    public function openDataIssues(): HasMany
    {
        return $this->dataIssues()->whereNull('resolved_at');
    }

    public function isOverdue(): bool
    {
        return $this->status !== IncidentStatus::Resolved
            && $this->created_at->copy()->addHours($this->priority->slaHours())->isPast();
    }

    /**
     * @param  array{status?: ?string, priority?: ?string, q?: ?string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $q, string $priority) => $q->where('priority', $priority))
            ->when($filters['q'] ?? null, function (Builder $q, string $text) {
                // Se escapan los comodines para que "100%" busque ese texto literal.
                $like = '%'.addcslashes($text, '\\%_').'%';

                $q->where(fn (Builder $w) => $w
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('requester_name', 'like', $like)
                    ->orWhere('assigned_to', 'like', $like));
            });
    }
}
