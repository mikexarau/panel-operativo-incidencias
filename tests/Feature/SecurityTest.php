<?php

namespace Tests\Feature;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function reservedActorNames(): array
    {
        return [
            'identidad exacta del diagnóstico' => ['system:diagnostic'],
            'sin distinguir mayúsculas' => ['SYSTEM:diagnostic'],
        ];
    }

    #[DataProvider('reservedActorNames')]
    public function test_nobody_can_sign_as_the_system(string $name): void
    {
        $incident = Incident::factory()->create(['status' => IncidentStatus::InReview]);

        $this->post('/incidents', ['actor_name' => $name])->assertSessionHasErrors('actor_name');
        $this->post("/incidents/{$incident->id}/comments", ['body' => 'Hola', 'actor_name' => $name])->assertSessionHasErrors('actor_name');
        $this->patch("/incidents/{$incident->id}/status", [
            'status' => 'resolved',
            'expected_status' => 'in_review',
            'actor_name' => $name,
        ])->assertSessionHasErrors('actor_name');

        $this->assertSame(IncidentStatus::InReview, $incident->fresh()->status);
    }

    public function test_a_malformed_status_is_a_validation_error_and_not_a_server_error(): void
    {
        $incident = Incident::factory()->create();

        $this->patch("/incidents/{$incident->id}/status", ['status' => ['resolved'], 'actor_name' => 'Operador'])
            ->assertSessionHasErrors('status');
    }

    public function test_html_in_a_comment_is_stored_as_plain_text_for_react_to_escape(): void
    {
        $incident = Incident::factory()->create();

        $this->post("/incidents/{$incident->id}/comments", [
            'body' => '<script>alert(1)</script>',
            'actor_name' => 'Operador',
        ])->assertSessionHasNoErrors();

        $this->assertSame('<script>alert(1)</script>', $incident->comments()->sole()->body);
    }

    public function test_responses_carry_the_security_headers(): void
    {
        $this->withoutVite();
        Vite::useHotFile(storage_path('framework/hot-inexistente'));

        $response = $this->get('/incidents');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $this->assertStringContainsString("script-src 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_writes_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->post('/incidents', [])->assertStatus(302);
        }

        $this->post('/incidents', [])->assertStatus(429);
    }

    public function test_a_non_numeric_incident_id_is_not_found(): void
    {
        $this->post('/incidents/abc/comments', [])->assertNotFound();
    }
}
