<?php

namespace App\Http\Requests;

use App\Enums\IncidentStatus;
use App\Http\Requests\Concerns\ValidatesActorName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeIncidentStatusRequest extends FormRequest
{
    use ValidatesActorName;

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(IncidentStatus::class)],
            'expected_status' => ['required', Rule::enum(IncidentStatus::class)],
            'actor_name' => $this->actorNameRules(),
            'reason' => [Rule::requiredIf(fn () => $this->targetRequiresReason()), 'nullable', 'string', 'max:1000'],
        ];
    }

    private function targetRequiresReason(): bool
    {
        $status = $this->input('status');

        return is_string($status)
            && IncidentStatus::tryFrom($status)?->requiresReasonFrom($this->route('incident')->status);
    }
}
