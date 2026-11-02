<?php

namespace App\Diagnostics;

enum ConsistencyRule: string
{
    case ResolvedWithoutLog = 'resolved_without_log';
    case StatusLogMismatch = 'status_log_mismatch';
    case DuplicateLog = 'duplicate_log';
    case IncoherentLog = 'incoherent_log';

    public function label(): string
    {
        return match ($this) {
            self::ResolvedWithoutLog => 'Resuelta sin log de resolución',
            self::StatusLogMismatch => 'El estado no coincide con su último log',
            self::DuplicateLog => 'Log duplicado',
            self::IncoherentLog => 'Log incoherente',
        };
    }
}
