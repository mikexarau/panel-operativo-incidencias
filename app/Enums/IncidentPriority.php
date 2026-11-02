<?php

namespace App\Enums;

enum IncidentPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
            self::Critical => 'Crítica',
        };
    }

    /**
     * Plazo orientativo de atencion. El esquema no tiene fecha limite, asi que
     * una incidencia "vencida" se deriva de su prioridad y su antiguedad.
     */
    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 168,
            self::Medium => 72,
            self::High => 24,
            self::Critical => 4,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
