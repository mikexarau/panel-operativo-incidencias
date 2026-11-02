<?php

namespace Database\Seeders;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Simula a proposito datos corruptos para el ejercicio de mantenimiento.
 *
 * Usa DB::table y no las acciones del dominio: esas inconsistencias nacen de
 * cambios hechos por SQL a mano, importaciones o codigo antiguo sin transaccion.
 */
class InconsistentIncidentSeeder extends Seeder
{
    public function run(): void
    {
        $this->resolvedWithoutResolutionLog();
        $this->statusDisagreesWithLastLog();
        $this->exactDuplicateLog();
        $this->ambiguousDuplicateLog();
        $this->incoherentLog();
    }

    /** El estado se cambio directamente en base de datos: falta el log de resolucion. */
    private function resolvedWithoutResolutionLog(): void
    {
        $openedAt = now()->subDays(6);

        $id = $this->incident('Fuga de agua en el almacén', 'Tienda Sant Cugat', IncidentStatus::Resolved, $openedAt, $openedAt->copy()->addDay());

        $this->log($id, IncidentLogAction::Created, null, IncidentStatus::Open, 'Coordinación', $openedAt);
        $this->log($id, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::InReview, 'Coordinación', $openedAt->copy()->addHours(2));
    }

    /** El ultimo log dice "resuelta", pero la incidencia figura como bloqueada. */
    private function statusDisagreesWithLastLog(): void
    {
        $openedAt = now()->subDays(5);
        $resolvedAt = $openedAt->copy()->addHours(8);

        $id = $this->incident('Cámara frigorífica con alarma de temperatura', 'Tienda Terrassa', IncidentStatus::Blocked, $openedAt, $resolvedAt);

        $this->log($id, IncidentLogAction::Created, null, IncidentStatus::Open, 'Coordinación', $openedAt);
        $this->log($id, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::InReview, 'Coordinación', $openedAt->copy()->addHour());
        $this->log($id, IncidentLogAction::StatusChanged, IncidentStatus::InReview, IncidentStatus::Resolved, 'Frío Industrial Prat', $resolvedAt);
    }

    /** Doble envio: misma transicion del mismo operador con dos segundos de diferencia. */
    private function exactDuplicateLog(): void
    {
        $openedAt = now()->subDays(4);
        $reviewAt = $openedAt->copy()->addHours(3);

        $id = $this->incident('Rótulo exterior apagado', 'Tienda Reus', IncidentStatus::InReview, $openedAt, $reviewAt);

        $this->log($id, IncidentLogAction::Created, null, IncidentStatus::Open, 'Coordinación', $openedAt);
        $this->log($id, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::InReview, 'Coordinación', $reviewAt);
        $this->log($id, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::InReview, 'Coordinación', $reviewAt->copy()->addSeconds(2));
    }

    /** Misma transicion, pero de otra persona y tres horas despues: no se puede asumir que sobre. */
    private function ambiguousDuplicateLog(): void
    {
        $openedAt = now()->subDays(3);
        $firstAt = $openedAt->copy()->addHour();

        $id = $this->incident('Central de incendios con aviso de avería', 'Tienda Lleida', IncidentStatus::InReview, $openedAt, $firstAt->copy()->addHours(3));

        $this->log($id, IncidentLogAction::Created, null, IncidentStatus::Open, 'Coordinación', $openedAt);
        $this->log($id, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::InReview, 'Coordinación', $firstAt);
        $this->log($id, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::InReview, 'Protección Contra Incendios Vallès', $firstAt->copy()->addHours(3));
    }

    /** Salta de abierta a resuelta sin pasar por revision: la maquina de estados no lo permite. */
    private function incoherentLog(): void
    {
        $openedAt = now()->subDays(2);
        $resolvedAt = $openedAt->copy()->addHours(5);

        $id = $this->incident('Persiana metálica no baja al cerrar', 'Tienda Manresa', IncidentStatus::Resolved, $openedAt, $resolvedAt);

        $this->log($id, IncidentLogAction::Created, null, IncidentStatus::Open, 'Coordinación', $openedAt);
        $this->log($id, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::Resolved, 'Importación CSV', $resolvedAt);
    }

    private function incident(string $title, string $store, IncidentStatus $status, Carbon $createdAt, Carbon $updatedAt): int
    {
        return DB::table('incidents')->insertGetId([
            'title' => $title,
            'description' => "Registro simulado con datos inconsistentes para el diagnóstico ({$store}).",
            'status' => $status->value,
            'priority' => IncidentPriority::Medium->value,
            'requester_name' => $store,
            'assigned_to' => null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
    }

    private function log(int $incidentId, IncidentLogAction $action, ?IncidentStatus $old, ?IncidentStatus $new, string $user, Carbon $at): void
    {
        DB::table('incident_logs')->insert([
            'incident_id' => $incidentId,
            'action' => $action->value,
            'old_value' => $old?->value,
            'new_value' => $new?->value,
            'user_name' => $user,
            'created_at' => $at,
        ]);
    }
}
