<?php

namespace Database\Seeders;

use App\Actions\Incidents\ChangeIncidentStatus;
use App\Actions\Incidents\CreateIncident;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Datos sanos: cada escenario se reproduce con las acciones reales del dominio
 * viajando en el tiempo, asi que sus logs son coherentes por construccion.
 */
class IncidentSeeder extends Seeder
{
    public function __construct(
        private CreateIncident $createIncident,
        private ChangeIncidentStatus $changeStatus,
    ) {}

    public function run(): void
    {
        foreach ($this->scenarios() as $scenario) {
            $this->play($scenario);
        }
    }

    private function play(array $scenario): void
    {
        $openedAt = now()->subHours($scenario['opened_hours_ago']);

        Carbon::setTestNow($openedAt);

        $incident = $this->createIncident->handle([
            'title' => $scenario['title'],
            'description' => $scenario['description'],
            'priority' => $scenario['priority']->value,
            'requester_name' => $scenario['requester'],
            'assigned_to' => $scenario['assigned_to'],
        ], 'Coordinación');

        foreach ($scenario['steps'] ?? [] as [$hoursLater, $status, $actor, $reason]) {
            Carbon::setTestNow($openedAt->copy()->addHours($hoursLater));

            $incident = $this->changeStatus->handle($incident, $status, $incident->status, $actor, $reason);
        }

        foreach ($scenario['comments'] ?? [] as [$hoursLater, $author, $body]) {
            Carbon::setTestNow($openedAt->copy()->addHours($hoursLater));

            $incident->comments()->create(['author_name' => $author, 'body' => $body]);
        }

        Carbon::setTestNow();
    }

    private function scenarios(): array
    {
        return [
            [
                'title' => 'Tienda sin climatización en plena jornada',
                'description' => 'La máquina de cubierta se detiene con error de alta presión y la sala supera los 29 grados.',
                'priority' => IncidentPriority::Critical,
                'requester' => 'Marta Soler (Tienda Sants)',
                'assigned_to' => 'Clima Levante',
                'opened_hours_ago' => 7,
                'steps' => [[1, IncidentStatus::InReview, 'Coordinación', null]],
                'comments' => [[2, 'Clima Levante', 'Técnico en ruta. Llegada estimada en una hora.']],
            ],
            [
                'title' => 'Cuadro eléctrico dispara el diferencial de la zona de cajas',
                'description' => 'Dos cajas se quedan sin corriente cada pocos minutos. Hay cola en la línea de cobro.',
                'priority' => IncidentPriority::Critical,
                'requester' => 'Iván Ferrer (Tienda Gran Vía 2)',
                'assigned_to' => null,
                'opened_hours_ago' => 2,
            ],
            [
                'title' => 'Puerta automática del acceso principal no abre',
                'description' => 'El sensor no detecta el paso y la puerta queda cerrada. Se atiende por el acceso lateral.',
                'priority' => IncidentPriority::High,
                'requester' => 'Laura Pons (Tienda Maremagnum)',
                'assigned_to' => 'Accesos Martí',
                'opened_hours_ago' => 30,
                'steps' => [
                    [2, IncidentStatus::InReview, 'Coordinación', null],
                    [6, IncidentStatus::Blocked, 'Accesos Martí', 'Pendiente de recibir el sensor de repuesto del fabricante.'],
                ],
                'comments' => [[7, 'Accesos Martí', 'Pedido el repuesto. El proveedor confirma envío en 48 horas.']],
            ],
            [
                'title' => 'Extintor caducado en la trastienda',
                'description' => 'La revisión anual muestra un extintor fuera de fecha junto a la salida de emergencia.',
                'priority' => IncidentPriority::High,
                'requester' => 'Pau Riera (Tienda Glòries)',
                'assigned_to' => 'Protección Contra Incendios Vallès',
                'opened_hours_ago' => 8,
                'steps' => [[3, IncidentStatus::InReview, 'Coordinación', null]],
            ],
            [
                'title' => 'Fluorescentes apagados en el pasillo central',
                'description' => 'Tres luminarias sin funcionar. No afecta a la evacuación, pero la zona queda oscura.',
                'priority' => IncidentPriority::Medium,
                'requester' => 'Nuria Vidal (Tienda Diagonal Mar)',
                'assigned_to' => null,
                'opened_hours_ago' => 20,
            ],
            [
                'title' => 'Avisos de plagas en el almacén',
                'description' => 'El equipo de tienda ha visto indicios de roedores tras la última descarga de mercancía.',
                'priority' => IncidentPriority::High,
                'requester' => 'Sergi Mas (Tienda Mataró Parc)',
                'assigned_to' => 'Control de Plagas Maresme',
                'opened_hours_ago' => 60,
                'steps' => [
                    [4, IncidentStatus::InReview, 'Coordinación', null],
                    [28, IncidentStatus::Resolved, 'Control de Plagas Maresme', null],
                ],
                'comments' => [[28, 'Control de Plagas Maresme', 'Tratamiento aplicado y cebos instalados. Próxima visita en 30 días.']],
            ],
            [
                'title' => 'Baño de clientes con el grifo goteando',
                'description' => 'Pérdida constante de agua en el lavabo. Se ha colocado un aviso de fuera de servicio.',
                'priority' => IncidentPriority::Low,
                'requester' => 'Clara Bosch (Tienda La Maquinista)',
                'assigned_to' => 'Fontanería Ribas',
                'opened_hours_ago' => 96,
                'steps' => [
                    [24, IncidentStatus::InReview, 'Coordinación', null],
                    [50, IncidentStatus::Resolved, 'Fontanería Ribas', null],
                    [70, IncidentStatus::Open, 'Clara Bosch', 'El goteo ha vuelto a aparecer dos días después de la reparación.'],
                ],
            ],
            [
                'title' => 'Limpieza extraordinaria tras reforma del probador',
                'description' => 'Queda polvo de obra en la zona de probadores. Hay que dejarla lista antes de abrir.',
                'priority' => IncidentPriority::Medium,
                'requester' => 'Oriol Serra (Tienda Sabadell)',
                'assigned_to' => 'Limpiezas Costa',
                'opened_hours_ago' => 40,
                'steps' => [
                    [5, IncidentStatus::InReview, 'Coordinación', null],
                    [20, IncidentStatus::Resolved, 'Limpiezas Costa', null],
                ],
            ],
            [
                'title' => 'Persiana del escaparate hace ruido al subir',
                'description' => 'Funciona, pero el motor roza. Conviene revisarlo antes de que se quede atascada.',
                'priority' => IncidentPriority::Low,
                'requester' => 'Ana Castells (Tienda Girona)',
                'assigned_to' => null,
                'opened_hours_ago' => 12,
            ],
            [
                'title' => 'Medición de consumo fuera de rango en la instalación',
                'description' => 'El contador registra un consumo nocturno un 30 % superior a la media del último trimestre.',
                'priority' => IncidentPriority::Medium,
                'requester' => 'Departamento de Energía',
                'assigned_to' => 'Ingeniería Enernova',
                'opened_hours_ago' => 90,
                'steps' => [
                    [10, IncidentStatus::InReview, 'Coordinación', null],
                    [30, IncidentStatus::Blocked, 'Ingeniería Enernova', 'Hace falta que la propiedad del centro comercial autorice el acceso al cuadro general.'],
                    [60, IncidentStatus::InReview, 'Coordinación', null],
                ],
            ],
        ];
    }
}
