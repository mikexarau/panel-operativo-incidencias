<?php

namespace App\Models;

use App\Enums\IncidentPriority;
use App\Enums\IncidentQuickFilter;
use App\Enums\IncidentSort;
use App\Enums\IncidentStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property IncidentStatus $status
 * @property IncidentPriority $priority
 * @property string $requester_name
 * @property ?string $assigned_to
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
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

    public function dueAt(): CarbonInterface
    {
        return $this->created_at->copy()->addHours($this->priority->slaHours());
    }

    public function isOverdue(): bool
    {
        return $this->status !== IncidentStatus::Resolved && $this->dueAt()->isPast();
    }

    /**
     * Situación del plazo calculada aquí para que el frontend no dependa del reloj del
     * navegador ni repita la regla. Una incidencia resuelta ya no tiene plazo.
     *
     * @return array{minutes_remaining: int, elapsed_ratio: float}|null
     */
    public function slaStatus(): ?array
    {
        if ($this->status === IncidentStatus::Resolved) {
            return null;
        }

        $slaMinutes = $this->priority->slaHours() * 60;
        $elapsedMinutes = (now()->getTimestamp() - $this->created_at->getTimestamp()) / 60;

        return [
            'minutes_remaining' => (int) round($slaMinutes - $elapsedMinutes),
            'elapsed_ratio' => round($elapsedMinutes / $slaMinutes, 3),
        ];
    }

    /**
     * @param  array{status?: ?string, priority?: ?string, q?: ?string, flag?: ?string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $q, string $priority) => $q->where('priority', $priority))
            ->when(($filters['flag'] ?? null) === IncidentQuickFilter::Overdue->value, fn (Builder $q) => $q->overdue())
            ->when(($filters['flag'] ?? null) === IncidentQuickFilter::DataReview->value, fn (Builder $q) => $q->has('openDataIssues'))
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

    /**
     * Misma regla que isOverdue(), expresada en SQL para poder filtrar y contar.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', IncidentStatus::Resolved->value)
            ->where(function (Builder $byPriority) {
                foreach (IncidentPriority::cases() as $priority) {
                    $byPriority->orWhere(fn (Builder $q) => $q
                        ->where('priority', $priority->value)
                        ->where('created_at', '<', now()->subHours($priority->slaHours())));
                }
            });
    }

    public function scopeSortedBy(Builder $query, IncidentSort $sort): Builder
    {
        return match ($sort) {
            IncidentSort::Recent => $query->latest()->latest('id'),
            IncidentSort::Urgency => $query
                ->orderByRaw('status = ? ASC', [IncidentStatus::Resolved->value])
                ->orderByRaw('DATE_ADD(created_at, INTERVAL CASE priority '.$this->slaHoursCases().' END HOUR) ASC')
                ->latest('id'),
        };
    }

    /**
     * Fragmento SQL construido solo con constantes del enum, nunca con entrada de usuario.
     */
    private function slaHoursCases(): string
    {
        return collect(IncidentPriority::cases())
            ->map(fn (IncidentPriority $priority) => "WHEN '{$priority->value}' THEN {$priority->slaHours()}")
            ->implode(' ');
    }
}
