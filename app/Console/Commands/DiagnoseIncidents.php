<?php

namespace App\Console\Commands;

use App\Diagnostics\Finding;
use App\Diagnostics\IncidentConsistencyChecker;
use App\Diagnostics\IncidentConsistencyRepairer;
use App\Models\IncidentDataIssue;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class DiagnoseIncidents extends Command
{
    protected $signature = 'incidents:diagnose
        {--fix : Aplica las correcciones seguras y registra el resto para revisión manual}
        {--force : Con --fix, no pide confirmación}';

    protected $description = 'Detecta inconsistencias entre incidencias y logs. Sin --fix es una simulación y no modifica datos.';

    public function handle(IncidentConsistencyChecker $checker, IncidentConsistencyRepairer $repairer): int
    {
        $findings = $checker->findings();
        $this->renderFindings($findings);

        if (! $this->option('fix')) {
            if ($findings->isNotEmpty()) {
                $this->components->info('Simulación: no se ha modificado ningún dato. Ejecuta con --fix para aplicar la acción propuesta.');
            }

            return $findings->isEmpty() ? self::SUCCESS : self::FAILURE;
        }

        if ($findings->isNotEmpty() && ! $this->option('force') && ! $this->confirm('¿Aplicar estas acciones?')) {
            $this->components->warn('Cancelado: no se ha modificado ningún dato.');

            return self::FAILURE;
        }

        return $this->applyFixes($findings, $repairer, $checker);
    }

    /**
     * @param  Collection<int, Finding>  $findings
     */
    private function applyFixes(Collection $findings, IncidentConsistencyRepairer $repairer, IncidentConsistencyChecker $checker): int
    {
        $runId = (string) Str::uuid();
        $results = [];
        $failures = 0;

        foreach ($findings as $finding) {
            try {
                $results[] = [$finding->incidentId, $finding->rule->value, $repairer->apply($finding, $runId)];
            } catch (Throwable $e) {
                $failures++;
                $results[] = [$finding->incidentId, $finding->rule->value, "ERROR: {$e->getMessage()}"];
            }
        }

        if ($results !== []) {
            $this->table(['Incidencia', 'Regla', 'Resultado'], $results);
        }

        if (array_intersect(array_column($results, 2), [IncidentConsistencyRepairer::FIXED, IncidentConsistencyRepairer::FLAGGED]) !== []) {
            $this->components->info("Ejecución {$runId}. Búscala en metadata.run_id de incident_logs y en details.run_id de incident_data_issues.");
        }

        $closed = $repairer->closeStaleIssues($checker->findings());
        $pending = IncidentDataIssue::query()->whereNull('resolved_at')->count();

        if ($closed > 0) {
            $this->components->info("Revisiones cerradas porque el problema ya no existe: {$closed}.");
        }

        if ($pending > 0) {
            $this->components->warn("Quedan {$pending} inconsistencias en revisión manual. Ciérralas con incidents:resolve-issue.");
        }

        return $pending === 0 && $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  Collection<int, Finding>  $findings
     */
    private function renderFindings(Collection $findings): void
    {
        if ($findings->isEmpty()) {
            $this->components->info('No se han detectado inconsistencias.');

            return;
        }

        $openIssues = IncidentDataIssue::query()->whereNull('resolved_at')->pluck('id', 'fingerprint');

        $this->table(
            ['Incidencia', 'Regla', 'Evidencia', 'Acción propuesta', 'Tratamiento'],
            $findings->map(fn (Finding $finding) => [
                $finding->incidentId,
                $finding->rule->value,
                wordwrap($finding->evidence, 46),
                wordwrap($finding->proposedAction, 40),
                $finding->autoFixable ? 'Automático' : $this->manualTreatment($openIssues, $finding),
            ]),
        );

        $this->line("Hallazgos: {$findings->count()}. Automáticos: {$findings->where('autoFixable', true)->count()}. Manuales: {$findings->where('autoFixable', false)->count()}.");
    }

    private function manualTreatment(Collection $openIssues, Finding $finding): string
    {
        return $openIssues->has($finding->fingerprint)
            ? "Manual (revisión #{$openIssues[$finding->fingerprint]})"
            : 'Manual';
    }
}
