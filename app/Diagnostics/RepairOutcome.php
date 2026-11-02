<?php

namespace App\Diagnostics;

/**
 * Resultado de aplicar un hallazgo del diagnóstico.
 */
enum RepairOutcome: string
{
    case Fixed = 'corregida';
    case Flagged = 'marcada para revisión';
    case AlreadyFlagged = 'ya estaba en revisión';
    case Skipped = 'omitida: los datos han cambiado';

    public function changedData(): bool
    {
        return $this === self::Fixed || $this === self::Flagged;
    }
}
