<?php

namespace App\Enums;

/**
 * Filtros rápidos del panel: lo que requiere atención, sea cual sea su estado.
 */
enum IncidentQuickFilter: string
{
    case Overdue = 'overdue';
    case DataReview = 'data_review';

    public function label(): string
    {
        return match ($this) {
            self::Overdue => 'Vencidas',
            self::DataReview => 'Revisión de datos',
        };
    }
}
