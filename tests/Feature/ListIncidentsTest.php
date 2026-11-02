<?php

namespace Tests\Feature;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListIncidentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_it_filters_by_status_and_priority_and_counts_respect_the_other_filters(): void
    {
        Incident::factory()->create(['title' => 'Coincide', 'status' => IncidentStatus::Blocked, 'priority' => IncidentPriority::High]);
        Incident::factory()->create(['title' => 'Otro estado', 'status' => IncidentStatus::Open, 'priority' => IncidentPriority::High]);
        Incident::factory()->create(['title' => 'Otra prioridad', 'status' => IncidentStatus::Blocked, 'priority' => IncidentPriority::Low]);

        $this->get('/incidents?status=blocked&priority=high')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Incidents/Index', false)
                ->has('incidents.data', 1)
                ->where('incidents.data.0.title', 'Coincide')
                ->where('statusSummary.0.value', 'open')
                ->where('statusSummary.0.count', 1)
                ->where('statusSummary.2.value', 'blocked')
                ->where('statusSummary.2.count', 1)
            );
    }

    public function test_free_text_treats_like_wildcards_literally(): void
    {
        Incident::factory()->create(['title' => 'Descuento del 100% en caja']);
        Incident::factory()->create(['title' => 'Avería en caja']);

        $this->get('/incidents?q='.urlencode('100%'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents.data', 1)
                ->where('incidents.data.0.title', 'Descuento del 100% en caja')
            );

        $this->get('/incidents?q='.urlencode('%'))
            ->assertInertia(fn (Assert $page) => $page->has('incidents.data', 1));
    }

    public function test_an_invalid_filter_redirects_to_the_clean_list(): void
    {
        $this->get('/incidents?status=inventado')->assertRedirect('/incidents');
    }

    public function test_only_unresolved_incidents_past_their_sla_are_overdue(): void
    {
        $late = Incident::factory()->create(['priority' => IncidentPriority::Critical]);
        Incident::whereKey($late->id)->update(['created_at' => now()->subHours(5)]);

        $resolved = Incident::factory()->create(['priority' => IncidentPriority::Critical, 'status' => IncidentStatus::Resolved]);
        Incident::whereKey($resolved->id)->update(['created_at' => now()->subHours(5)]);

        $this->assertTrue($late->fresh()->isOverdue());
        $this->assertFalse($resolved->fresh()->isOverdue());
    }

    public function test_it_adds_a_comment_and_shows_it_with_the_history(): void
    {
        $incident = Incident::factory()->create();

        $this->post("/incidents/{$incident->id}/comments", ['body' => ''])
            ->assertSessionHasErrors(['body', 'actor_name']);

        $this->post("/incidents/{$incident->id}/comments", ['body' => 'Técnico avisado', 'actor_name' => 'Operador'])
            ->assertSessionHasNoErrors();

        $this->get("/incidents?incident={$incident->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedIncident.id', $incident->id)
                ->where('selectedIncident.comments.0.body', 'Técnico avisado')
                ->where('selectedIncident.comments.0.author_name', 'Operador')
            );
    }
}
