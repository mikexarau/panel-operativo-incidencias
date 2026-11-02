<?php

namespace App\Actions\Incidents;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Incident;
use Illuminate\Support\Facades\DB;

class ChangeIncidentStatus
{
    /**
     * Cambia el estado y escribe su log en la misma transacción. El bloqueo de
     * fila serializa cambios concurrentes, y $expected evita aplicar una decisión
     * tomada sobre un estado que otro operador ya ha modificado.
     *
     * @throws InvalidStatusTransition
     */
    public function handle(
        Incident $incident,
        IncidentStatus $target,
        IncidentStatus $expected,
        string $actor,
        ?string $reason = null,
    ): Incident {
        return DB::transaction(function () use ($incident, $target, $expected, $actor, $reason) {
            $locked = Incident::query()->lockForUpdate()->findOrFail($incident->id);
            $current = $locked->status;

            if ($current !== $expected) {
                throw InvalidStatusTransition::staleStatus($current);
            }

            if (! $current->canTransitionTo($target)) {
                throw InvalidStatusTransition::notAllowed($current, $target);
            }

            $locked->update(['status' => $target]);

            $locked->logs()->create([
                'action' => $this->logAction($current, $target),
                'old_value' => $current->value,
                'new_value' => $target->value,
                'user_name' => $actor,
                'metadata' => $reason !== null ? ['reason' => $reason] : null,
            ]);

            return $locked;
        });
    }

    private function logAction(IncidentStatus $from, IncidentStatus $to): IncidentLogAction
    {
        return $from === IncidentStatus::Resolved && $to === IncidentStatus::Open
            ? IncidentLogAction::Reopened
            : IncidentLogAction::StatusChanged;
    }
}
