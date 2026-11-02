<?php

namespace App\Enums;

/**
 * Catálogo de estados y máquina de estados de una incidencia.
 *
 * Es la única fuente de verdad: backend, frontend (vía props) y diagnóstico
 * leen de aquí los valores, etiquetas y transiciones permitidas.
 */
enum IncidentStatus: string
{
    case Open = 'open';
    case InReview = 'in_review';
    case Blocked = 'blocked';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::InReview => 'En revisión',
            self::Blocked => 'Bloqueada',
            self::Resolved => 'Resuelta',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::InReview],
            self::InReview => [self::Blocked, self::Resolved],
            self::Blocked => [self::InReview],
            self::Resolved => [self::Open],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Pasar a este estado desde $from exige justificar el motivo:
     * bloquear (qué se espera y de quién) y reabrir (por qué no estaba resuelta).
     */
    public function requiresReasonFrom(self $from): bool
    {
        return $this === self::Blocked || ($this === self::Open && $from === self::Resolved);
    }

    /**
     * Verbo que ve el operador en el botón que lleva a este estado desde $from.
     */
    public function actionLabelFrom(self $from): string
    {
        return match (true) {
            $this === self::Open => 'Reabrir',
            $this === self::InReview && $from === self::Blocked => 'Desbloquear',
            $this === self::InReview => 'Pasar a revisión',
            $this === self::Blocked => 'Bloquear',
            $this === self::Resolved => 'Resolver',
        };
    }

    /**
     * Etiqueta de un valor guardado en un log, que puede estar fuera de catálogo: entonces
     * se muestra tal cual, porque el log conserva lo que ocurrió.
     */
    public static function labelFor(?string $value): ?string
    {
        return $value === null ? null : (self::tryFrom($value)?->label() ?? $value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
