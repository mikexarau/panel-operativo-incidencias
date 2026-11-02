<?php

namespace Tests\Feature;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class ChangeIncidentStatusTest extends TestCase
{
    use RefreshDatabase;

    private function change(Incident $incident, string $status, array $overrides = []): TestResponse
    {
        return $this->patch("/incidents/{$incident->id}/status", [
            'status' => $status,
            'expected_status' => $incident->status->value,
            'actor_name' => 'Operador',
            ...$overrides,
        ]);
    }

    public function test_an_allowed_change_updates_the_status_and_writes_its_log(): void
    {
        $incident = Incident::factory()->create(['status' => IncidentStatus::InReview]);

        $this->change($incident, 'resolved')->assertSessionHasNoErrors();

        $log = $incident->logs()->sole();

        $this->assertSame(IncidentStatus::Resolved, $incident->fresh()->status);
        $this->assertSame(IncidentLogAction::StatusChanged, $log->action);
        $this->assertSame('in_review', $log->old_value);
        $this->assertSame('resolved', $log->new_value);
        $this->assertSame('Operador', $log->user_name);
    }

    public function test_a_forbidden_change_is_rejected_without_side_effects(): void
    {
        $incident = Incident::factory()->create(['status' => IncidentStatus::Open]);

        $this->change($incident, 'resolved')->assertSessionHasErrors('status');

        $this->assertSame(IncidentStatus::Open, $incident->fresh()->status);
        $this->assertDatabaseCount('incident_logs', 0);
    }

    public function test_reopening_needs_a_reason_and_leaves_an_explicit_log(): void
    {
        $incident = Incident::factory()->create(['status' => IncidentStatus::Resolved]);

        $this->change($incident, 'open')->assertSessionHasErrors('reason');
        $this->assertSame(IncidentStatus::Resolved, $incident->fresh()->status);

        $this->change($incident, 'open', ['reason' => 'La avería ha vuelto a aparecer'])
            ->assertSessionHasNoErrors();

        $log = $incident->logs()->sole();

        $this->assertSame(IncidentStatus::Open, $incident->fresh()->status);
        $this->assertSame(IncidentLogAction::Reopened, $log->action);
        $this->assertSame('La avería ha vuelto a aparecer', $log->metadata['reason']);
    }

    public function test_a_decision_taken_on_a_stale_status_is_rejected(): void
    {
        $incident = Incident::factory()->create(['status' => IncidentStatus::Blocked]);

        // El operador vio "en revisión", pero otro la ha bloqueado mientras tanto.
        $this->change($incident, 'in_review', ['expected_status' => 'in_review'])
            ->assertSessionHasErrors('status');

        $this->assertSame(IncidentStatus::Blocked, $incident->fresh()->status);
        $this->assertDatabaseCount('incident_logs', 0);
    }

    public function test_the_status_change_is_rolled_back_if_its_log_cannot_be_written(): void
    {
        $incident = Incident::factory()->create(['status' => IncidentStatus::Open]);

        IncidentLog::creating(fn () => throw new RuntimeException('Fallo al escribir el log'));

        $this->withoutExceptionHandling();

        try {
            $this->change($incident, 'in_review');
            $this->fail('La acción debería propagar el fallo.');
        } catch (RuntimeException $e) {
            $this->assertSame('Fallo al escribir el log', $e->getMessage());
        }

        $this->assertSame(IncidentStatus::Open, $incident->fresh()->status);
    }

    public function test_logs_cannot_be_rewritten_or_deleted(): void
    {
        $incident = Incident::factory()->create();
        $log = $incident->logs()->create([
            'action' => IncidentLogAction::Created,
            'new_value' => 'open',
            'user_name' => 'Operador',
        ]);

        $log->new_value = 'resolved';
        $this->assertThrows(fn () => $log->save(), \LogicException::class);

        $log->refresh();
        $this->assertThrows(fn () => $log->delete(), \LogicException::class);

        $log->voided_at = now();
        $log->save();
        $this->assertNotNull($log->fresh()->voided_at);
    }
}
