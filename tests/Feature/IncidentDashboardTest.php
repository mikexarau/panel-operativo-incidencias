<?php

namespace Tests\Feature;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IncidentDashboardTest extends TestCase
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
                ->where('summary.statuses.0.value', 'open')
                ->where('summary.statuses.0.count', 1)
                ->where('summary.statuses.2.value', 'blocked')
                ->where('summary.statuses.2.count', 1)
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

    public function test_the_overdue_filter_matches_the_model_rule(): void
    {
        $late = Incident::factory()->create(['priority' => IncidentPriority::Critical]);
        Incident::whereKey($late->id)->update(['created_at' => now()->subHours(5)]);

        $inTime = Incident::factory()->create(['priority' => IncidentPriority::Low]);
        Incident::whereKey($inTime->id)->update(['created_at' => now()->subHours(5)]);

        $resolved = Incident::factory()->create(['priority' => IncidentPriority::Critical, 'status' => IncidentStatus::Resolved]);
        Incident::whereKey($resolved->id)->update(['created_at' => now()->subHours(5)]);

        $this->assertSame([$late->id], Incident::overdue()->pluck('id')->all());
        $this->assertSame(
            Incident::overdue()->pluck('id')->all(),
            Incident::all()->filter->isOverdue()->pluck('id')->values()->all(),
        );

        $this->get('/incidents?flag=overdue')
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents.data', 1)
                ->where('incidents.data.0.id', $late->id)
                ->where('incidents.data.0.sla.minutes_remaining', fn (int $minutes) => $minutes < 0)
                ->where('summary.quick_filters.0.value', 'overdue')
                ->where('summary.quick_filters.0.count', 1)
                ->where('summary.quick_filters.1.count', 0)
            );
    }

    public function test_it_sorts_by_urgency_and_leaves_resolved_incidents_last(): void
    {
        $resolved = Incident::factory()->create(['priority' => IncidentPriority::Critical, 'status' => IncidentStatus::Resolved]);
        $low = Incident::factory()->create(['priority' => IncidentPriority::Low]);
        $critical = Incident::factory()->create(['priority' => IncidentPriority::Critical]);
        Incident::whereKey($resolved->id)->update(['created_at' => now()->subDays(30)]);

        $this->get('/incidents?sort=urgency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('incidents.data.0.id', $critical->id)
                ->where('incidents.data.1.id', $low->id)
                ->where('incidents.data.2.id', $resolved->id)
                ->where('incidents.data.2.sla', null)
            );
    }

    public function test_priority_load_only_counts_unresolved_incidents_most_critical_first(): void
    {
        Incident::factory()->create(['priority' => IncidentPriority::Critical]);
        Incident::factory()->create(['priority' => IncidentPriority::Critical, 'status' => IncidentStatus::Resolved]);

        $this->get('/incidents')
            ->assertInertia(fn (Assert $page) => $page
                ->where('priorityLoad.0.value', 'critical')
                ->where('priorityLoad.0.count', 1)
            );
    }

    public function test_recent_activity_lists_the_latest_changes_with_their_incident(): void
    {
        $this->post('/incidents', [
            'title' => 'Puerta automática atascada',
            'description' => 'No abre.',
            'priority' => 'high',
            'requester_name' => 'Marta Soler',
            'actor_name' => 'Operador',
        ])->assertSessionHasNoErrors();

        $this->get('/incidents')
            ->assertInertia(fn (Assert $page) => $page
                ->where('recentActivity.0.incident_title', 'Puerta automática atascada')
                ->where('recentActivity.0.user_name', 'Operador')
                ->where('recentActivity.0.new_label', 'Abierta')
            );
    }
}
