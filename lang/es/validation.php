<?php

return [
    'enum' => 'El valor de :attribute no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser un texto.',

    'custom' => [
        'actor_name' => [
            'required' => 'Indica tu nombre para dejar constancia en el historial.',
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
        'incident' => 'incidencia',
        'priority' => 'prioridad',
        'q' => 'búsqueda',
        'reason' => 'motivo',
        'requester_name' => 'solicitante',
        'status' => 'estado',
        'title' => 'título',
    ],
];
