<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesActorName;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentCommentRequest extends FormRequest
{
    use ValidatesActorName;

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'actor_name' => $this->actorNameRules(),
        ];
    }
}
