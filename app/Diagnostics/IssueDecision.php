<?php

namespace App\Diagnostics;

/**
 * Decisión humana con la que se cierra una inconsistencia en revisión manual.
 */
enum IssueDecision: string
{
    /** La incidencia tiene razón: se añade un log que recoge su estado actual. */
    case KeepStatus = 'keep-status';

    /** El último log tiene razón: la incidencia adopta su valor. */
    case AdoptLog = 'adopt-log';

    /** El log es erróneo: se anula, sin borrarlo. */
    case VoidLog = 'void-log';

    /** Anomalía histórica conocida: se conserva y deja de reportarse. */
    case Accept = 'accept';

    /** Cierre automático: el diagnóstico ya no detecta el problema. */
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
