<?php

namespace App\Http\Requests;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListIncidentsRequest extends FormRequest
{
    /** Un filtro invalido en la URL lleva al listado limpio, nunca de vuelta a la misma URL. */
    protected $redirectRoute = 'incidents.index';

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(IncidentStatus::class)],
            'priority' => ['nullable', Rule::enum(IncidentPriority::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'incident' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array{status: ?string, priority: ?string, q: ?string}
     */
    public function filters(): array
    {
        return [
            'status' => $this->validated('status'),
            'priority' => $this->validated('priority'),
            'q' => $this->validated('q'),
        ];
    }
}
