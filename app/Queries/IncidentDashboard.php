<?php

namespace App\Queries;

use App\Enums\IncidentPriority;
use App\Enums\IncidentQuickFilter;
use App\Enums\IncidentSort;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

/**
 * Lecturas de la pantalla principal. Cada indicador ignora el filtro que él mismo
 * controla: así cada cifra dice cuántas incidencias se verán al pulsarlo.
 */
class IncidentDashboard
{
    private const PER_PAGE = 15;

    private const ACTIVITY_LIMIT = 8;

    public function list(array $filters): LengthAwarePaginator
    {
        return Incident::query()
            ->filter($filters)
            ->withCount('openDataIssues')
            ->sortedBy(IncidentSort::from($filters['sort']))
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array{statuses: list<array{value: string, label: string, count: int}>, quick_filters: list<array{value: string, label: string, count: int}>}
     */
    public function summary(array $filters): array
    {
        $byStatus = Incident::query()
            ->filter(Arr::except($filters, 'status'))
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'statuses' => array_map(fn (IncidentStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($byStatus[$status->value] ?? 0),
            ], IncidentStatus::cases()),
            'quick_filters' => array_map(fn (IncidentQuickFilter $flag) => [
                'value' => $flag->value,
                'label' => $flag->label(),
                'count' => Incident::query()
                    ->filter([...Arr::except($filters, 'flag'), 'flag' => $flag->value])
                    ->count(),
            ], IncidentQuickFilter::cases()),
        ];
    }

    /**
     * Carga pendiente, de mayor a menor prioridad: lo resuelto ya no ocupa a nadie.
     *
     * @return list<array{value: string, label: string, count: int}>
     */
    public function priorityLoad(array $filters): array
    {
        $byPriority = Incident::query()
            ->filter(Arr::except($filters, ['priority', 'status']))
            ->where('status', '!=', IncidentStatus::Resolved->value)
            ->selectRaw('priority, COUNT(*) AS total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        return array_map(fn (IncidentPriority $priority) => [
            'value' => $priority->value,
            'label' => $priority->label(),
            'count' => (int) ($byPriority[$priority->value] ?? 0),
        ], array_reverse(IncidentPriority::cases()));
    }

    /**
     * Últimos cambios en todo el sistema, independientes de los filtros.
     *
     * @return list<array<string, mixed>>
     */
    public function recentActivity(): array
    {
        return IncidentLog::query()
            ->active()
            ->with('incident:id,title')
            ->latest()
            ->latest('id')
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (IncidentLog $log) => [
                'id' => $log->id,
                'incident_id' => $log->incident_id,
                'incident_title' => $log->incident->title,
                'action_label' => $log->action->label(),
                'old_label' => IncidentStatus::labelFor($log->old_value),
                'new_label' => IncidentStatus::labelFor($log->new_value),
                'user_name' => $log->user_name,
                'created_at' => $log->created_at->toIso8601String(),
            ])
            ->all();
    }

    public function selected(int $id): ?Incident
    {
        return Incident::query()
            ->withCount('openDataIssues')
            ->with([
                'logs' => fn ($query) => $query->oldest()->oldest('id'),
                'comments' => fn ($query) => $query->oldest()->oldest('id'),
                'openDataIssues',
            ])
            ->find($id);
    }
}
