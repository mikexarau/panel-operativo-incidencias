<?php

namespace App\Enums;

/**
 * Catalogo de estados y maquina de estados de una incidencia.
 *
 * Es la unica fuente de verdad: backend, frontend (via props) y diagnostico
 * leen de aqui los valores, etiquetas y transiciones permitidas.
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
     * bloquear (que se espera y de quien) y reabrir (por que no estaba resuelta).
     */
    public function requiresReasonFrom(self $from): bool
    {
        return $this === self::Blocked || ($this === self::Open && $from === self::Resolved);
    }

    /**
     * Verbo que ve el operador en el boton que lleva a este estado desde $from.
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
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
