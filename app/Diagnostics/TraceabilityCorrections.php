<?php

namespace App\Diagnostics;

use App\Enums\IncidentLogAction;
use App\Models\Incident;
use App\Models\IncidentLog;

/**
 * Correcciones de trazabilidad: siempre anaden informacion, nunca la reescriben.
 * El llamante es responsable de la transaccion y del bloqueo.
 */
class TraceabilityCorrections
{
    public function appendReconciliation(Incident $incident, ?string $old, string $new, string $actor, array $metadata): IncidentLog
    {
        return $incident->logs()->create([
            'action' => IncidentLogAction::StatusReconciled,
            'old_value' => $old,
            'new_value' => $new,
            'user_name' => $actor,
            'metadata' => $metadata,
        ]);
    }

    public function voidLog(IncidentLog $log, string $actor, array $metadata): void
    {
        $log->voided_at = now();
        $log->save();

        $log->incident->logs()->create([
            'action' => IncidentLogAction::LogVoided,
            'user_name' => $actor,
            'metadata' => [...$metadata, 'voided_log_id' => $log->id],
        ]);
    }

    /**
     * Ultimo log de estado vigente de la incidencia.
     */
    public function lastStatusLog(Incident $incident): ?IncidentLog
    {
        return $incident->logs()
            ->active()
            ->whereIn('action', IncidentLogAction::statusBearing())
            ->latest()
            ->latest('id')
            ->first();
    }
}
