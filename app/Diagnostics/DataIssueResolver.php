<?php

namespace App\Diagnostics;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentDataIssue;
use App\Models\IncidentLog;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Ejecuta la decisión de una persona sobre una inconsistencia en revisión manual,
 * dejando en el historial quién decidió, qué y por qué.
 */
class DataIssueResolver
{
    public function __construct(private TraceabilityCorrections $corrections) {}

    /**
     * @throws DomainException si la decisión no es aplicable.
     */
    public function resolve(IncidentDataIssue $issue, IssueDecision $decision, string $actor, string $reason): void
    {
        if (! in_array($decision, IssueDecision::availableFor($issue->rule), true)) {
            throw new DomainException("La decisión {$decision->value} no se aplica a la regla {$issue->rule->value}.");
        }

        DB::transaction(function () use ($issue, $decision, $actor, $reason) {
            $incident = Incident::query()->lockForUpdate()->findOrFail($issue->incident_id);
            $issue = IncidentDataIssue::query()->lockForUpdate()->findOrFail($issue->id);

            if ($issue->resolved_at !== null) {
                throw new DomainException("La revisión #{$issue->id} ya está cerrada.");
            }

            $metadata = ['origin' => 'manual_review', 'issue_id' => $issue->id, 'reason' => $reason];

            match ($decision) {
                IssueDecision::KeepStatus => $this->keepStatus($incident, $actor, $metadata),
                IssueDecision::AdoptLog => $this->adoptLog($incident, $actor, $metadata),
                IssueDecision::VoidLog => $this->voidLog($issue, $actor, $metadata),
                IssueDecision::Accept => null,
            };

            $issue->forceFill([
                'resolved_at' => now(),
                'resolved_by' => $actor,
                'resolution' => $decision->value,
                'resolution_note' => $reason,
            ])->save();
        });
    }

    private function keepStatus(Incident $incident, string $actor, array $metadata): void
    {
        $this->corrections->appendReconciliation(
            $incident,
            $this->corrections->lastStatusLog($incident)?->new_value,
            $incident->status->value,
            $actor,
            $metadata,
        );
    }

    private function adoptLog(Incident $incident, string $actor, array $metadata): void
    {
        $target = IncidentStatus::tryFrom((string) $this->corrections->lastStatusLog($incident)?->new_value);

        if ($target === null) {
            throw new DomainException('El último log no contiene un estado válido que se pueda adoptar.');
        }

        $previous = $incident->status;
        $incident->update(['status' => $target]);

        $this->corrections->appendReconciliation($incident, $previous->value, $target->value, $actor, $metadata);
    }

    private function voidLog(IncidentDataIssue $issue, string $actor, array $metadata): void
    {
        $log = IncidentLog::query()->findOrFail($issue->details['log_id']);

        if ($log->voided_at !== null) {
            throw new DomainException("El log #{$log->id} ya está anulado.");
        }

        $this->corrections->voidLog($log, $actor, $metadata);
    }
}
