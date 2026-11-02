<?php

namespace App\Http\Requests;

use App\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeIncidentStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(IncidentStatus::class)],
            'expected_status' => ['required', Rule::enum(IncidentStatus::class)],
            'actor_name' => ['required', 'string', 'max:100'],
            'reason' => [Rule::requiredIf(fn () => $this->targetRequiresReason()), 'nullable', 'string', 'max:1000'],
        ];
    }

    private function targetRequiresReason(): bool
    {
        return IncidentStatus::tryFrom((string) $this->input('status'))
            ?->requiresReasonFrom($this->route('incident')->status) ?? false;
    }
}
