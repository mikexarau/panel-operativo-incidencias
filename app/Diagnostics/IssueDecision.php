<?php

namespace App\Diagnostics;

/**
 * Decision humana con la que se cierra una inconsistencia en revision manual.
 */
enum IssueDecision: string
{
    /** La incidencia tiene razon: se anade un log que recoge su estado actual. */
    case KeepStatus = 'keep-status';

    /** El ultimo log tiene razon: la incidencia adopta su valor. */
    case AdoptLog = 'adopt-log';

    /** El log es erroneo: se anula, sin borrarlo. */
    case VoidLog = 'void-log';

    /** Anomalia historica conocida: se conserva y deja de reportarse. */
    case Accept = 'accept';

    /** Cierre automatico: el diagnostico ya no detecta el problema. */
    case NoLongerDetected = 'no-longer-detected';

    /**
     * @return list<self>
     */
    public static function availableFor(ConsistencyRule $rule): array
    {
        return match ($rule) {
            ConsistencyRule::ResolvedWithoutLog, ConsistencyRule::StatusLogMismatch => [self::KeepStatus, self::AdoptLog],
            ConsistencyRule::DuplicateLog, ConsistencyRule::IncoherentLog => [self::VoidLog, self::Accept],
        };
    }
}
