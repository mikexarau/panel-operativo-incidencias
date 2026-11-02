<?php

namespace App\Console\Commands;

use App\Diagnostics\DataIssueResolver;
use App\Diagnostics\IssueDecision;
use App\Models\IncidentDataIssue;
use DomainException;
use Illuminate\Console\Command;

class ResolveIncidentIssue extends Command
{
    protected $signature = 'incidents:resolve-issue
        {issue : ID de la revisión en incident_data_issues}
        {--decision= : keep-status, adopt-log, void-log o accept}
        {--actor= : Quién toma la decisión}
        {--reason= : Motivo de la decisión}';

    protected $description = 'Cierra una inconsistencia en revisión manual con una decisión humana trazada.';

    public function handle(DataIssueResolver $resolver): int
    {
        $issue = IncidentDataIssue::query()->find($this->argument('issue'));

        if ($issue === null) {
            $this->components->error("No existe la revisión #{$this->argument('issue')}.");

            return self::FAILURE;
        }

        $decision = IssueDecision::tryFrom((string) $this->option('decision'));
        $actor = trim((string) $this->option('actor'));
        $reason = trim((string) $this->option('reason'));
        $available = implode(', ', array_map(fn (IssueDecision $d) => $d->value, IssueDecision::availableFor($issue->rule)));

        if ($decision === null || $actor === '' || $reason === '') {
            $this->components->error("Faltan datos. Indica --actor, --reason y --decision (para {$issue->rule->value}: {$available}).");

            return self::FAILURE;
        }

        try {
            $resolver->resolve($issue, $decision, $actor, $reason);
        } catch (DomainException $e) {
            $this->components->error("{$e->getMessage()} Decisiones disponibles: {$available}.");

            return self::FAILURE;
        }

        $this->components->info("Revisión #{$issue->id} cerrada con la decisión {$decision->value}.");

        return self::SUCCESS;
    }
}
