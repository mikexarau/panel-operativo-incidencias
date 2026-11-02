<?php

namespace App\Diagnostics;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentDataIssue;
use App\Models\IncidentLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Aplica lo que el diagnóstico propone. Nunca cambia incidents.status: solo añade
 * trazas compensatorias, anula copias evidentes o registra el caso para revisión.
 *
 * Cada hallazgo va en su propia transacción y se revalida dentro del bloqueo, porque
 * los datos pueden haber cambiado desde la detección.
 */
class IncidentConsistencyRepairer
{
    public const SYSTEM_ACTOR = 'system:diagnostic';

    public function __construct(private TraceabilityCorrections $corrections) {}

    public function apply(Finding $finding, string $runId): RepairOutcome
    {
        if (! $finding->autoFixable) {
            return $this->flagForReview($finding, $runId);
        }

        return match ($finding->rule) {
            ConsistencyRule::ResolvedWithoutLog => $this->reconstructResolutionLog($finding, $runId),
            ConsistencyRule::DuplicateLog => $this->voidDuplicate($finding, $runId),
            default => throw new LogicException("La regla {$finding->rule->value} nunca se corrige automáticamente."),
        };
    }

    /**
     * Cierra las revisiones abiertas cuyo problema ya no existe, por ejemplo porque
     * un operador ha corregido la incidencia desde el panel.
     *
     * @param  Collection<int, Finding>  $findings
     */
    public function closeStaleIssues(Collection $findings): int
    {
        return IncidentDataIssue::query()
            ->whereNull('resolved_at')
            ->whereNotIn('fingerprint', $findings->pluck('fingerprint'))
            ->update([
                'resolved_at' => now(),
                'resolved_by' => self::SYSTEM_ACTOR,
                'resolution' => IssueDecision::NoLongerDetected->value,
                'updated_at' => now(),
            ]);
    }

    private function reconstructResolutionLog(Finding $finding, string $runId): RepairOutcome
    {
        return DB::transaction(function () use ($finding, $runId) {
            $incident = Incident::query()->lockForUpdate()->findOrFail($finding->incidentId);

            $hasResolutionLog = $incident->logs()
                ->active()
                ->whereIn('action', IncidentLogAction::statusBearing())
                ->where('new_value', IncidentStatus::Resolved->value)
                ->exists();

            if ($incident->status !== IncidentStatus::Resolved || $hasResolutionLog) {
                return RepairOutcome::Skipped;
            }

            $this->corrections->appendReconciliation(
                $incident,
                $this->corrections->lastStatusLog($incident)?->new_value,
                IncidentStatus::Resolved->value,
                self::SYSTEM_ACTOR,
                [
                    'origin' => 'diagnostic_auto',
                    'rule' => $finding->rule->value,
                    'run_id' => $runId,
                    'estimated_occurred_at' => $incident->updated_at->toIso8601String(),
                    'reason' => 'Traza reconstruida por el diagnóstico. Se desconoce quién resolvió la incidencia; la hora real es aproximadamente la de updated_at.',
                ],
            );

            return RepairOutcome::Fixed;
        });
    }

    private function voidDuplicate(Finding $finding, string $runId): RepairOutcome
    {
        return DB::transaction(function () use ($finding, $runId) {
            Incident::query()->lockForUpdate()->findOrFail($finding->incidentId);

            $log = IncidentLog::query()->findOrFail($finding->logId);

            if ($log->voided_at !== null) {
                return RepairOutcome::Skipped;
            }

            $this->corrections->voidLog($log, self::SYSTEM_ACTOR, [
                'origin' => 'diagnostic_auto',
                'rule' => $finding->rule->value,
                'run_id' => $runId,
                'reason' => 'Copia de un doble envío: misma transición y mismo actor con segundos de diferencia.',
            ]);

            return RepairOutcome::Fixed;
        });
    }

    private function flagForReview(Finding $finding, string $runId): RepairOutcome
    {
        return DB::transaction(function () use ($finding, $runId) {
            $issue = IncidentDataIssue::query()->lockForUpdate()->firstOrNew(['fingerprint' => $finding->fingerprint]);

            if ($issue->exists && $issue->resolved_at === null) {
                return RepairOutcome::AlreadyFlagged;
            }

            $issue->fill([
                'incident_id' => $finding->incidentId,
                'rule' => $finding->rule,
                'details' => [
                    'log_id' => $finding->logId,
                    'evidence' => $finding->evidence,
                    'proposed_action' => $finding->proposedAction,
                    'run_id' => $runId,
                ],
            ]);
            $issue->forceFill(['resolved_at' => null, 'resolved_by' => null, 'resolution' => null, 'resolution_note' => null]);
            $issue->save();

            return RepairOutcome::Flagged;
        });
    }
}
