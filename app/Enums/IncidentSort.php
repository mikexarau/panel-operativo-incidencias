<?php

namespace App\Enums;

enum IncidentSort: string
{
    case Recent = 'recent';
    case Urgency = 'urgency';

    public function label(): string
    {
        return match ($this) {
            self::Recent => 'Más recientes',
            self::Urgency => 'Más urgentes',
        };
    }
}
