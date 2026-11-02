<?php

namespace Tests\Feature;

use App\Diagnostics\ConsistencyRule;
use App\Diagnostics\IncidentConsistencyChecker;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentDataIssue;
use Database\Seeders\IncidentSeeder;
use Database\Seeders\InconsistentIncidentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DiagnoseIncidentsTest extends TestCase
{
    use RefreshDatabase;

    private function snapshot(): array
    {
        return collect(['incidents', 'incident_logs', 'incident_comments', 'incident_data_issues'])
            ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->orderBy('id')->get()->all()])
            ->all();
    }

    private function incidentTitled(string $title): Incident
    {
        return Incident::where('title', $title)->sole();
    }

    private function fix(): void
    {
        $this->artisan('incidents:diagnose', ['--fix' => true, '--force' => true]);
    }

    public function test_healthy_data_produces_no_findings(): void
    {
        $this->seed(IncidentSeeder::class);

        $this->assertCount(0, app(IncidentConsistencyChecker::class)->findings());
        $this->artisan('incidents:diagnose')->assertSuccessful();
    }

    public function test_dry_run_reports_every_simulated_case_and_writes_nothing(): void
    {
        $this->seed(InconsistentIncidentSeeder::class);
        $before = $this->snapshot();

        $this->artisan('incidents:diagnose')->assertFailed();

        $this->assertEquals($before, $this->snapshot());

        $findings = app(IncidentConsistencyChecker::class)->findings();

        $this->assertEqualsCanonicalizing([
            [ConsistencyRule::ResolvedWithoutLog, true],
            [ConsistencyRule::StatusLogMismatch, false],
            [ConsistencyRule::DuplicateLog, true],
            [ConsistencyRule::DuplicateLog, false],
            [ConsistencyRule::IncoherentLog, false],
        ], $findings->map(fn ($f) => [$f->rule, $f->autoFixable])->all());
    }

    public function test_fix_repairs_safe_cases_flags_the_rest_and_never_changes_a_status(): void
    {
        $this->seed(InconsistentIncidentSeeder::class);
        $statuses = Incident::pluck('status', 'id');

        $this->fix();

        $this->assertEquals($statuses, Incident::pluck('status', 'id'));

        $reconciled = $this->incidentTitled('Fuga de agua en el almacén')->logs()->latest('id')->first();
        $this->assertSame(IncidentLogAction::StatusReconciled, $reconciled->action);
        $this->assertSame(IncidentStatus::InReview->value, $reconciled->old_value);
        $this->assertSame(IncidentStatus::Resolved->value, $reconciled->new_value);
        $this->assertSame('system:diagnostic', $reconciled->user_name);
        $this->assertArrayHasKey('estimated_occurred_at', $reconciled->metadata);

        $sign = $this->incidentTitled('Rótulo exterior apagado');
        $voided = $sign->logs()->whereNotNull('voided_at')->sole();
        $this->assertSame($voided->id, $sign->logs()->where('action', IncidentLogAction::LogVoided)->sole()->metadata['voided_log_id']);

        $this->assertEqualsCanonicalizing(
            [ConsistencyRule::StatusLogMismatch, ConsistencyRule::DuplicateLog, ConsistencyRule::IncoherentLog],
            IncidentDataIssue::whereNull('resolved_at')->get()->pluck('rule')->all(),
        );
    }

    public function test_fix_is_idempotent(): void
    {
        $this->seed(InconsistentIncidentSeeder::class);

        $this->fix();
        $afterFirstRun = $this->snapshot();

        $this->fix();

        $this->assertEquals($afterFirstRun, $this->snapshot());
    }

    public function test_a_resolved_incident_whose_last_log_is_newer_goes_to_manual_review(): void
    {
        $incident = Incident::factory()->create(['status' => IncidentStatus::Resolved]);
        Incident::whereKey($incident->id)->update(['updated_at' => now()->subDay()]);
        DB::table('incident_logs')->insert([
            'incident_id' => $incident->id,
            'action' => IncidentLogAction::Created->value,
            'new_value' => IncidentStatus::Open->value,
            'user_name' => 'Operador',
            'created_at' => now(),
        ]);

        $this->fix();

        $this->assertSame(1, $incident->logs()->count());
        $this->assertSame(ConsistencyRule::ResolvedWithoutLog, $incident->openDataIssues()->sole()->rule);
    }

    public function test_incoherent_logs_cover_no_op_and_implicit_reopen(): void
    {
        $insertLogs = function (Incident $incident, array $logs) {
            foreach ($logs as $i => [$action, $old, $new]) {
                DB::table('incident_logs')->insert([
                    'incident_id' => $incident->id,
                    'action' => $action->value,
                    'old_value' => $old,
                    'new_value' => $new,
                    'user_name' => 'Operador',
                    'created_at' => now()->subMinutes(10 - $i),
                ]);
            }
        };

        $reopened = Incident::factory()->create(['status' => IncidentStatus::Open]);
        $insertLogs($reopened, [
            [IncidentLogAction::Created, null, 'open'],
            [IncidentLogAction::StatusChanged, 'open', 'in_review'],
            [IncidentLogAction::StatusChanged, 'in_review', 'resolved'],
            [IncidentLogAction::StatusChanged, 'resolved', 'open'],
        ]);

        $noOp = Incident::factory()->create(['status' => IncidentStatus::Open]);
        $insertLogs($noOp, [
            [IncidentLogAction::Created, null, 'open'],
            [IncidentLogAction::StatusChanged, 'open', 'open'],
        ]);

        // El primer log de una incidencia también se comprueba, aunque no tenga anterior.
        $withoutValue = Incident::factory()->create(['status' => IncidentStatus::Open]);
        $insertLogs($withoutValue, [[IncidentLogAction::Created, null, null]]);

        $evidence = collect(app(IncidentConsistencyChecker::class)->incoherentLogs())->pluck('evidence', 'incidentId');

        $this->assertCount(3, $evidence);
        $this->assertStringContainsString('reapertura', $evidence[$reopened->id]);
        $this->assertStringContainsString('iguales', $evidence[$noOp->id]);
        $this->assertStringContainsString('catálogo', $evidence[$withoutValue->id]);
    }

    public function test_manual_decisions_leave_a_trace_and_the_diagnostic_converges_to_zero(): void
    {
        $this->seed(InconsistentIncidentSeeder::class);
        $this->fix();

        $resolve = fn (string $title, string $decision) => $this->artisan('incidents:resolve-issue', [
            'issue' => $this->incidentTitled($title)->openDataIssues()->sole()->id,
            '--decision' => $decision,
            '--actor' => 'Responsable de calidad',
            '--reason' => 'Revisado con la tienda',
        ])->assertSuccessful();

        $resolve('Cámara frigorífica con alarma de temperatura', 'adopt-log');
        $resolve('Central de incendios con aviso de avería', 'void-log');
        $resolve('Persiana metálica no baja al cerrar', 'accept');

        $fridge = $this->incidentTitled('Cámara frigorífica con alarma de temperatura');
        $this->assertSame(IncidentStatus::Resolved, $fridge->status);
        $this->assertSame('Responsable de calidad', $fridge->logs()->where('action', IncidentLogAction::StatusReconciled)->sole()->user_name);

        $this->artisan('incidents:diagnose')->assertSuccessful();
    }

    public function test_a_decision_that_does_not_fit_the_rule_is_rejected(): void
    {
        $this->seed(InconsistentIncidentSeeder::class);
        $this->fix();

        $issue = $this->incidentTitled('Cámara frigorífica con alarma de temperatura')->openDataIssues()->sole();

        $this->artisan('incidents:resolve-issue', [
            'issue' => $issue->id,
            '--decision' => 'void-log',
            '--actor' => 'Responsable de calidad',
            '--reason' => 'Prueba',
        ])->assertFailed();

        $this->assertNull($issue->fresh()->resolved_at);
    }

    public function test_an_open_review_is_closed_when_the_problem_disappears(): void
    {
        $this->seed(InconsistentIncidentSeeder::class);
        $this->fix();

        $fridge = $this->incidentTitled('Cámara frigorífica con alarma de temperatura');
        $issue = $fridge->openDataIssues()->sole();

        // Alguien corrige el dato sin usar resolve-issue: la cola no debe quedar desfasada.
        Incident::whereKey($fridge->id)->update(['status' => IncidentStatus::Resolved->value]);

        $this->fix();

        $this->assertSame('no-longer-detected', $issue->fresh()->resolution);
    }
}
