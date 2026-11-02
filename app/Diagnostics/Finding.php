<?php

namespace App\Diagnostics;

/**
 * Una inconsistencia detectada. Solo describe: no modifica datos.
 */
final readonly class Finding
{
    public function __construct(
        public ConsistencyRule $rule,
        public int $incidentId,
        public ?int $logId,
        public bool $autoFixable,
        public string $evidence,
        public string $proposedAction,
        public string $fingerprint,
    ) {}
}
