<?php

return [
    'enum' => 'El valor de :attribute no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
    ],
    'not_regex' => 'El formato de :attribute no es válido.',
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser un texto.',

    'custom' => [
        'actor_name' => [
            'required' => 'Indica tu nombre para dejar constancia en el historial.',
            'not_regex' => 'Los nombres que empiezan por "system:" están reservados para el sistema.',
        ],
        'reason' => [
            'required' => 'Indica el motivo: quedará registrado en el historial.',
        ],
    ],

    'attributes' => [
        'actor_name' => 'tu nombre',
        'assigned_to' => 'responsable',
        'body' => 'comentario',
        'description' => 'descripción',
        'expected_status' => 'estado esperado',
        'flag' => 'filtro rápido',
        'incident' => 'incidencia',
        'priority' => 'prioridad',
        'q' => 'búsqueda',
        'reason' => 'motivo',
        'requester_name' => 'solicitante',
        'sort' => 'orden',
        'status' => 'estado',
        'title' => 'título',
    ],
];
