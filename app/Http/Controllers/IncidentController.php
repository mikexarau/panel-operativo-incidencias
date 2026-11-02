<?php

namespace App\Http\Controllers;

use App\Actions\Incidents\CreateIncident;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Http\Requests\ListIncidentsRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Resources\IncidentDetailResource;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    /**
     * Las closures se evaluan solo si el frontend las pide: al filtrar se recargan
     * incidents y statusSummary, y al abrir una incidencia solo selectedIncident.
     */
    public function index(ListIncidentsRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('Incidents/Index', [
            'filters' => fn () => $filters,
            'priorities' => array_map(
                fn (IncidentPriority $priority) => ['value' => $priority->value, 'label' => $priority->label()],
                IncidentPriority::cases(),
            ),
            'statusSummary' => fn () => $this->statusSummary($filters),
            'incidents' => fn () => IncidentResource::collection(
                Incident::query()
                    ->filter($filters)
                    ->withCount('openDataIssues')
                    ->latest()
                    ->latest('id')
                    ->paginate(15)
                    ->withQueryString()
            ),
            'selectedIncident' => fn () => $this->selectedIncident($request->validated('incident')),
        ]);
    }

    public function store(StoreIncidentRequest $request, CreateIncident $createIncident): RedirectResponse
    {
        $createIncident->handle(
            $request->safe()->except('actor_name'),
            $request->validated('actor_name'),
        );

        return back()->with('success', 'Incidencia creada.');
    }

    /**
     * Contadores por estado respetando los demas filtros: cada cifra indica
     * cuantas incidencias se veran al pulsar ese estado.
     *
     * @return list<array{value: string, label: string, count: int}>
     */
    private function statusSummary(array $filters): array
    {
        $counts = Incident::query()
            ->filter(Arr::except($filters, 'status'))
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return array_map(fn (IncidentStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'count' => (int) ($counts[$status->value] ?? 0),
        ], IncidentStatus::cases());
    }

    private function selectedIncident(?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        $incident = Incident::query()
            ->withCount('openDataIssues')
            ->with(['logs' => fn ($q) => $q->oldest()->oldest('id'), 'comments' => fn ($q) => $q->oldest()->oldest('id'), 'openDataIssues'])
            ->find($id);

        return $incident ? IncidentDetailResource::make($incident)->resolve() : null;
    }
}
