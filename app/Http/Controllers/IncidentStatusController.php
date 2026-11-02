<?php

namespace App\Http\Controllers;

use App\Actions\Incidents\ChangeIncidentStatus;
use App\Enums\IncidentStatus;
use App\Http\Requests\ChangeIncidentStatusRequest;
use App\Models\Incident;
use Illuminate\Http\RedirectResponse;

class IncidentStatusController extends Controller
{
    public function update(
        ChangeIncidentStatusRequest $request,
        Incident $incident,
        ChangeIncidentStatus $changeStatus,
    ): RedirectResponse {
        $target = IncidentStatus::from($request->validated('status'));

        $changeStatus->handle(
            $incident,
            $target,
            IncidentStatus::from($request->validated('expected_status')),
            $request->validated('actor_name'),
            $request->validated('reason'),
        );

        return back()->with('success', "Incidencia {$target->label()}.");
    }
}
