<?php

namespace Tests\Feature;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateIncidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_open_incident_with_its_creation_log(): void
    {
        $this->post('/incidents', [
            'title' => 'Puerta automática atascada',
            'description' => 'No abre desde la apertura de la tienda.',
            'priority' => 'high',
            'requester_name' => 'Marta Soler',
            'assigned_to' => null,
            'actor_name' => 'Operador',
            // Un cliente no puede fijar el estado inicial.
            'status' => 'resolved',
        ])->assertSessionHasNoErrors();

        $incident = Incident::sole();
        $log = $incident->logs()->sole();

        $this->assertSame(IncidentStatus::Open, $incident->status);
        $this->assertSame(IncidentLogAction::Created, $log->action);
        $this->assertNull($log->old_value);
        $this->assertSame('open', $log->new_value);
        $this->assertSame('Operador', $log->user_name);
    }

    public function test_invalid_data_is_rejected_with_spanish_messages_and_nothing_is_stored(): void
    {
        $this->post('/incidents', ['title' => 'x', 'priority' => 'urgentisima'])
            ->assertSessionHasErrors([
                'title' => 'El campo título debe tener al menos 5 caracteres.',
                'description' => 'El campo descripción es obligatorio.',
                'priority' => 'El valor de prioridad no es válido.',
                'actor_name' => 'Indica tu nombre para dejar constancia en el historial.',
            ]);

        $this->assertDatabaseCount('incidents', 0);
        $this->assertDatabaseCount('incident_logs', 0);
    }
}
