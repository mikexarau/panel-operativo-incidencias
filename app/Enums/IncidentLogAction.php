<?php

namespace App\Enums;

enum IncidentLogAction: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case Reopened = 'reopened';

    /** Compensa un hueco de trazabilidad detectado por el diagnostico o decidido en revision manual. */
    case StatusReconciled = 'status_reconciled';

    /** Deja constancia de que otro log se ha anulado. No se borra ningun log. */
    case LogVoided = 'log_voided';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Creación',
            self::StatusChanged => 'Cambio de estado',
            self::Reopened => 'Reapertura',
            self::StatusReconciled => 'Estado reconciliado',
            self::LogVoided => 'Log anulado',
        };
    }

    /**
     * Acciones cuyos old_value/new_value describen el estado de la incidencia.
     *
     * @return list<self>
     */
    public static function statusBearing(): array
    {
        return [self::Created, self::StatusChanged, self::Reopened, self::StatusReconciled];
    }

    /**
     * Acciones que siguen la maquina de estados normal. Una reconciliacion es
     * compensatoria por definicion y queda fuera de las comprobaciones de cadena.
     *
     * @return list<self>
     */
    public static function regularTransitions(): array
    {
        return [self::Created, self::StatusChanged, self::Reopened];
    }

    /**
     * @param  list<self>  $actions
     * @return list<string>
     */
    public static function valuesOf(array $actions): array
    {
        return array_map(fn (self $action) => $action->value, $actions);
    }
}
