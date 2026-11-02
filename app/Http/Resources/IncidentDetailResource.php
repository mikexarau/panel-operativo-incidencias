<?php

namespace App\Http\Resources;

use App\Enums\IncidentStatus;
use App\Models\IncidentComment;
use App\Models\IncidentDataIssue;
use App\Models\IncidentLog;
use Illuminate\Http\Request;

class IncidentDetailResource extends IncidentResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'logs' => $this->logs->map(fn (IncidentLog $log) => [
                'id' => $log->id,
                'action' => $log->action->value,
                'action_label' => $log->action->label(),
                'old_label' => $this->statusLabel($log->old_value),
                'new_label' => $this->statusLabel($log->new_value),
                'user_name' => $log->user_name,
                'reason' => $log->metadata['reason'] ?? null,
                'created_at' => $log->created_at->toIso8601String(),
                'voided_at' => $log->voided_at?->toIso8601String(),
            ]),
            'comments' => $this->comments->map(fn (IncidentComment $comment) => [
                'id' => $comment->id,
                'author_name' => $comment->author_name,
                'body' => $comment->body,
                'created_at' => $comment->created_at->toIso8601String(),
            ]),
            'open_data_issues' => $this->openDataIssues->map(fn (IncidentDataIssue $issue) => [
                'id' => $issue->id,
                'rule_label' => $issue->rule->label(),
                'proposed_action' => $issue->details['proposed_action'] ?? null,
            ]),
        ];
    }

    /**
     * Un log puede contener valores fuera de catálogo: se muestran tal cual.
     */
    private function statusLabel(?string $value): ?string
    {
        return $value === null ? null : (IncidentStatus::tryFrom($value)?->label() ?? $value);
    }
}
