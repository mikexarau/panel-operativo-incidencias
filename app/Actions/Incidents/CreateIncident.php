<?php

namespace App\Actions\Incidents;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Support\Facades\DB;

class CreateIncident
{
    /**
     * @param  array{title: string, description: string, priority: string, requester_name: string, assigned_to?: ?string}  $attributes
     */
    public function handle(array $attributes, string $actor): Incident
    {
        return DB::transaction(function () use ($attributes, $actor) {
            $incident = Incident::create([...$attributes, 'status' => IncidentStatus::Open]);

            $incident->logs()->create([
                'action' => IncidentLogAction::Created,
                'new_value' => $incident->status->value,
                'user_name' => $actor,
            ]);

            return $incident;
        });
    }
}
