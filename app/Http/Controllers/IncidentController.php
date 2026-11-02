<?php

namespace App\Http\Controllers;

use App\Actions\Incidents\CreateIncident;
use App\Enums\IncidentPriority;
use App\Enums\IncidentSort;
use App\Http\Requests\ListIncidentsRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Resources\IncidentDetailResource;
use App\Http\Resources\IncidentResource;
use App\Queries\IncidentDashboard;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    /**
     * Las closures solo se evalúan si el frontend las pide: al filtrar se recarga el
     * listado y sus indicadores, y al abrir una incidencia solo selectedIncident.
     */
    public function index(ListIncidentsRequest $request, IncidentDashboard $dashboard): Response
    {
        $filters = $request->filters();
        $selectedId = $request->validated('incident');

        return Inertia::render('Incidents/Index', [
            'filters' => fn () => $filters,
            'priorities' => $this->options(IncidentPriority::cases()),
            'sorts' => $this->options(IncidentSort::cases()),
            'summary' => fn () => $dashboard->summary($filters),
            'priorityLoad' => fn () => $dashboard->priorityLoad($filters),
            'recentActivity' => fn () => $dashboard->recentActivity(),
            'incidents' => fn () => IncidentResource::collection($dashboard->list($filters)),
            'selectedIncident' => function () use ($dashboard, $selectedId) {
                $incident = $selectedId === null ? null : $dashboard->selected((int) $selectedId);

                return $incident ? IncidentDetailResource::make($incident)->resolve() : null;
            },
        ]);
    }

    public function store(StoreIncidentRequest $request, CreateIncident $createIncident): RedirectResponse
    {
        $createIncident->handle(
            $request->safe()->except('actor_name'),
            $request->validated('actor_name'),
        );

        return Inertia::flash('success', 'Incidencia creada.')->back();
    }

    /**
     * @param  list<IncidentPriority|IncidentSort>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function options(array $cases): array
    {
        return array_map(fn ($case) => ['value' => $case->value, 'label' => $case->label()], $cases);
    }
}
