<?php

namespace App\Exceptions;

use App\Enums\IncidentStatus;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Cambio de estado rechazado por una regla de negocio. Es un error esperado:
 * no se reporta y llega al operador como un error de validacion mas.
 */
class InvalidStatusTransition extends RuntimeException
{
    public static function notAllowed(IncidentStatus $from, IncidentStatus $to): self
    {
        return new self("Una incidencia {$from->label()} no puede pasar a {$to->label()}.");
    }

    public static function staleStatus(IncidentStatus $current): self
    {
        return new self("Otro operador ha cambiado la incidencia, que ahora está {$current->label()}. Revisa su estado antes de continuar.");
    }

    public function report(): void
    {
        //
    }

    public function render(): RedirectResponse
    {
        return back()->withErrors(['status' => $this->getMessage()]);
    }
}
