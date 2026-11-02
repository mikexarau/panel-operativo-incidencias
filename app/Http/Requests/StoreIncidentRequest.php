<?php

namespace App\Http\Requests;

use App\Enums\IncidentPriority;
use App\Http\Requests\Concerns\ValidatesActorName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    use ValidatesActorName;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(IncidentPriority::class)],
            'requester_name' => ['required', 'string', 'max:100'],
            'assigned_to' => ['nullable', 'string', 'max:100'],
            'actor_name' => $this->actorNameRules(),
        ];
    }
}
