<?php

namespace App\Http\Resources;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Incident
 */
class IncidentResource extends JsonResource
{
    /**
     * Las transiciones viajan con cada incidencia para que el frontend pinte las
     * acciones sin duplicar la máquina de estados en JavaScript.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'requester_name' => $this->requester_name,
            'assigned_to' => $this->assigned_to,
            'created_at' => $this->created_at->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
            'sla' => $this->slaStatus(),
            'needs_attention' => $this->isOverdue() || $this->status === IncidentStatus::Blocked,
            'open_data_issues_count' => $this->whenCounted('openDataIssues'),
            'allowed_transitions' => array_map(fn (IncidentStatus $target) => [
                'status' => $target->value,
                'status_label' => $target->label(),
                'action_label' => $target->actionLabelFrom($this->status),
                'requires_reason' => $target->requiresReasonFrom($this->status),
            ], $this->status->allowedTransitions()),
        ];
    }
}
