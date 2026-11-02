<?php

namespace Tests\Unit;

use App\Enums\IncidentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IncidentStatusTest extends TestCase
{
    /**
     * @return array<string, array{IncidentStatus, IncidentStatus, bool}>
     */
    public static function transitions(): array
    {
        $allowed = [
            'open>in_review',
            'in_review>blocked',
            'in_review>resolved',
            'blocked>in_review',
            'resolved>open',
        ];

        $matrix = [];

        foreach (IncidentStatus::cases() as $from) {
            foreach (IncidentStatus::cases() as $to) {
                $key = "{$from->value}>{$to->value}";
                $matrix[$key] = [$from, $to, in_array($key, $allowed, true)];
            }
        }

        return $matrix;
    }

    #[DataProvider('transitions')]
    public function test_transition_matrix(IncidentStatus $from, IncidentStatus $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    public function test_blocking_and_reopening_require_a_reason(): void
    {
        $this->assertTrue(IncidentStatus::Blocked->requiresReasonFrom(IncidentStatus::InReview));
        $this->assertTrue(IncidentStatus::Open->requiresReasonFrom(IncidentStatus::Resolved));
        $this->assertFalse(IncidentStatus::Resolved->requiresReasonFrom(IncidentStatus::InReview));
        $this->assertFalse(IncidentStatus::InReview->requiresReasonFrom(IncidentStatus::Open));
    }
}
