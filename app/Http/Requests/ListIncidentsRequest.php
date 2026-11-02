<?php

namespace App\Http\Requests;

use App\Enums\IncidentPriority;
use App\Enums\IncidentQuickFilter;
use App\Enums\IncidentSort;
use App\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListIncidentsRequest extends FormRequest
{
    /** Un filtro inválido en la URL lleva al listado limpio, nunca de vuelta a la misma URL. */
    protected $redirectRoute = 'incidents.index';

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(IncidentStatus::class)],
            'priority' => ['nullable', Rule::enum(IncidentPriority::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'flag' => ['nullable', Rule::enum(IncidentQuickFilter::class)],
            'sort' => ['nullable', Rule::enum(IncidentSort::class)],
            'incident' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array{status: ?string, priority: ?string, q: ?string, flag: ?string, sort: string}
     */
    public function filters(): array
    {
        return [
            'status' => $this->validated('status'),
            'priority' => $this->validated('priority'),
            'q' => $this->validated('q'),
            'flag' => $this->validated('flag'),
            'sort' => $this->validated('sort') ?? IncidentSort::Recent->value,
        ];
    }
}
