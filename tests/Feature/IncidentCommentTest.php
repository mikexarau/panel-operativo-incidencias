<?php

namespace Tests\Feature;

use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IncidentCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_a_comment_and_shows_it_with_the_history(): void
    {
        $this->withoutVite();

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
