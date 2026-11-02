<?php

namespace App\Diagnostics;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\IncidentDataIssue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Detecta inconsistencias entre incidents e incident_logs. Solo lee.
 *
 * Cada regla es una consulta identificable, documentada en el README. Los valores
 * salen de los enums para no repetir estados a mano; son constantes, no entrada
 * de usuario, y por eso se interpolan y la consulta queda legible.
 */
class IncidentConsistencyChecker
{
    /** Por debajo de esta separación, dos logs idénticos del mismo actor son un doble envío. */
    public const DOUBLE_SUBMIT_SECONDS = 5;

    /**
     * @return Collection<int, Finding>
     */
    public function findings(): Collection
    {
        $accepted = IncidentDataIssue::query()
            ->where('resolution', IssueDecision::Accept->value)
            ->pluck('fingerprint')
            ->flip();

        return collect([
            ...$this->resolvedWithoutLog(),
            ...$this->statusLogMismatch(),
            ...$this->duplicateLogs(),
            ...$this->incoherentLogs(),
        ])->reject(fn (Finding $finding) => $accepted->has($finding->fingerprint))->values();
    }

    /**
     * @return list<Finding>
     */
    public function resolvedWithoutLog(): array
    {
        $resolved = IncidentStatus::Resolved->value;

        $rows = DB::select(<<<SQL
            WITH last_status_log AS ({$this->lastStatusLogSql()})
            SELECT i.id AS incident_id, i.updated_at,
                   last_log.id AS last_log_id, last_log.new_value AS last_new_value, last_log.created_at AS last_log_at
            FROM incidents i
            LEFT JOIN last_status_log last_log ON last_log.incident_id = i.id AND last_log.recency = 1
            WHERE i.status = '{$resolved}'
              AND {$this->hasNoResolutionLogSql()}
            SQL);

        return array_map(function (object $row) {
            // Si el último log es posterior al último cambio de la fila, contradice
            // el estado en vez de faltar una traza: no se puede asumir que esté bien.
            $autoFixable = $row->last_log_at === null
                || Carbon::parse($row->updated_at)->gte(Carbon::parse($row->last_log_at));

            return new Finding(
                rule: ConsistencyRule::ResolvedWithoutLog,
                incidentId: $row->incident_id,
                logId: $row->last_log_id,
                autoFixable: $autoFixable,
                evidence: 'status=resolved sin ningún log hacia resolved; último log: '.($row->last_new_value ?? 'ninguno'),
                proposedAction: $autoFixable
                    ? 'Añadir log status_reconciled con actor del sistema y hora estimada en metadata'
                    : 'Revisión manual: el último log es posterior a updated_at y contradice el estado',
                fingerprint: "resolved_without_log:{$row->incident_id}",
            );
        }, $rows);
    }

    /**
     * @return list<Finding>
     */
    public function statusLogMismatch(): array
    {
        $resolved = IncidentStatus::Resolved->value;

        $rows = DB::select(<<<SQL
            WITH last_status_log AS ({$this->lastStatusLogSql()})
            SELECT i.id AS incident_id, i.status, i.updated_at,
                   last_log.id AS last_log_id, last_log.new_value AS last_new_value, last_log.created_at AS last_log_at
            FROM incidents i
            LEFT JOIN last_status_log last_log ON last_log.incident_id = i.id AND last_log.recency = 1
            WHERE NOT (last_log.new_value <=> i.status)
              AND NOT (i.status = '{$resolved}' AND {$this->hasNoResolutionLogSql()})
            SQL);

        return array_map(fn (object $row) => new Finding(
            rule: ConsistencyRule::StatusLogMismatch,
            incidentId: $row->incident_id,
            logId: $row->last_log_id,
            autoFixable: false,
            evidence: "status={$row->status} (updated_at {$row->updated_at}); último log: "
                .($row->last_log_id ? "#{$row->last_log_id} -> {$row->last_new_value} ({$row->last_log_at})" : 'ninguno'),
            proposedAction: 'Revisión manual: una persona debe decidir si manda el estado o el log',
            fingerprint: "status_log_mismatch:{$row->incident_id}:{$row->status}:".($row->last_log_id ?? 'none'),
        ), $rows);
    }

    /**
     * @return list<Finding>
     */
    public function duplicateLogs(): array
    {
        $rows = DB::select(<<<SQL
            WITH ordered AS ({$this->orderedStatusLogsSql()})
            SELECT id, incident_id, old_value, new_value, user_name, prev_id, prev_user,
                   TIMESTAMPDIFF(SECOND, prev_at, created_at) AS seconds_apart
            FROM ordered
            WHERE {$this->repeatsPreviousLogSql()}
            SQL);

        return array_map(function (object $row) {
            $doubleSubmit = $row->user_name === $row->prev_user
                && $row->seconds_apart <= self::DOUBLE_SUBMIT_SECONDS;

            return new Finding(
                rule: ConsistencyRule::DuplicateLog,
                incidentId: $row->incident_id,
                logId: $row->id,
                autoFixable: $doubleSubmit,
                evidence: "log #{$row->id} repite a #{$row->prev_id} ({$row->old_value} -> {$row->new_value}), "
                    ."{$row->seconds_apart} s después; actores: {$row->prev_user} / {$row->user_name}",
                proposedAction: $doubleSubmit
                    ? "Anular la copia #{$row->id} y conservar el original #{$row->prev_id}"
                    : 'Revisión manual: la misma transición, pero con otro actor o separada en el tiempo',
                fingerprint: "duplicate_log:{$row->id}",
            );
        }, $rows);
    }

    /**
     * @return list<Finding>
     */
    public function incoherentLogs(): array
    {
        $statuses = $this->sqlList(IncidentStatus::values());
        $regular = $this->sqlList(IncidentLogAction::valuesOf(IncidentLogAction::regularTransitions()));
        $created = IncidentLogAction::Created->value;
        $statusChanged = IncidentLogAction::StatusChanged->value;
        $resolved = IncidentStatus::Resolved->value;
        $open = IncidentStatus::Open->value;

        $rows = DB::select(<<<SQL
            WITH ordered AS ({$this->orderedStatusLogsSql()})
            SELECT id, incident_id, action, old_value, new_value, prev_id, prev_new,
                   CASE
                       WHEN new_value IS NULL OR new_value NOT IN ({$statuses}) THEN 'value_out_of_catalog'
                       WHEN old_value <=> new_value THEN 'no_op'
                       WHEN action = '{$statusChanged}' AND old_value = '{$resolved}' AND new_value = '{$open}' THEN 'implicit_reopen'
                       WHEN action <> '{$created}' AND (COALESCE(old_value, ''), new_value) NOT IN ({$this->allowedTransitionsSql()}) THEN 'forbidden_transition'
                       WHEN prev_id IS NOT NULL AND NOT (old_value <=> prev_new) THEN 'broken_chain'
                   END AS problem
            FROM ordered
            WHERE action IN ({$regular})
              AND NOT ({$this->repeatsPreviousLogSql()})
            HAVING problem IS NOT NULL
            SQL);

        return array_map(fn (object $row) => new Finding(
            rule: ConsistencyRule::IncoherentLog,
            incidentId: $row->incident_id,
            logId: $row->id,
            autoFixable: false,
            evidence: "log #{$row->id} ".($row->old_value ?? 'null').' -> '.($row->new_value ?? 'null').': '.$this->describeProblem($row),
            proposedAction: 'Revisión manual: los datos no permiten deducir qué ocurrió',
            fingerprint: "incoherent_log:{$row->id}",
        ), $rows);
    }

    private function describeProblem(object $row): string
    {
        return match ($row->problem) {
            'value_out_of_catalog' => 'valor fuera del catálogo de estados',
            'no_op' => 'el valor anterior y el nuevo son iguales',
            'implicit_reopen' => 'reapertura sin acción explícita de reopened',
            'forbidden_transition' => 'transición no permitida por la máquina de estados',
            'broken_chain' => "rompe la cadena: el log anterior terminó en {$row->prev_new}",
        };
    }

    /**
     * Logs de estado vigentes numerados del más reciente al más antiguo por incidencia.
     */
    private function lastStatusLogSql(): string
    {
        $actions = $this->sqlList(IncidentLogAction::valuesOf(IncidentLogAction::statusBearing()));

        return <<<SQL
            SELECT l.id, l.incident_id, l.new_value, l.created_at,
                   ROW_NUMBER() OVER (PARTITION BY l.incident_id ORDER BY l.created_at DESC, l.id DESC) AS recency
            FROM incident_logs l
            WHERE l.action IN ({$actions}) AND l.voided_at IS NULL
            SQL;
    }

    /**
     * Logs de estado vigentes en orden cronológico, cada uno junto a los datos del anterior.
     */
    private function orderedStatusLogsSql(): string
    {
        $actions = $this->sqlList(IncidentLogAction::valuesOf(IncidentLogAction::statusBearing()));

        return <<<SQL
            SELECT l.id, l.incident_id, l.action, l.old_value, l.new_value, l.user_name, l.created_at,
                   LAG(l.id) OVER w AS prev_id,
                   LAG(l.action) OVER w AS prev_action,
                   LAG(l.old_value) OVER w AS prev_old,
                   LAG(l.new_value) OVER w AS prev_new,
                   LAG(l.user_name) OVER w AS prev_user,
                   LAG(l.created_at) OVER w AS prev_at
            FROM incident_logs l
            WHERE l.action IN ({$actions}) AND l.voided_at IS NULL
            WINDOW w AS (PARTITION BY l.incident_id ORDER BY l.created_at, l.id)
            SQL;
    }

    private function repeatsPreviousLogSql(): string
    {
        return 'action <=> prev_action AND old_value <=> prev_old AND new_value <=> prev_new';
    }

    private function hasNoResolutionLogSql(): string
    {
        $actions = $this->sqlList(IncidentLogAction::valuesOf(IncidentLogAction::statusBearing()));
        $resolved = IncidentStatus::Resolved->value;

        return <<<SQL
            NOT EXISTS (
                SELECT 1 FROM incident_logs r
                WHERE r.incident_id = i.id
                  AND r.action IN ({$actions})
                  AND r.new_value = '{$resolved}'
                  AND r.voided_at IS NULL
            )
            SQL;
    }

    private function allowedTransitionsSql(): string
    {
        $pairs = [];

        foreach (IncidentStatus::cases() as $from) {
            foreach ($from->allowedTransitions() as $to) {
                $pairs[] = "('{$from->value}', '{$to->value}')";
            }
        }

        return implode(', ', $pairs);
    }

    /**
     * @param  list<string>  $values
     */
    private function sqlList(array $values): string
    {
        return implode(', ', array_map(fn (string $value) => "'{$value}'", $values));
    }
}
