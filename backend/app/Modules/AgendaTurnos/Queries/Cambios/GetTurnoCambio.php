<?php

namespace App\Modules\AgendaTurnos\Queries\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Exceptions\TurnoNotFoundException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\AgendaTurnos\Support\MotivoTurnoResolver;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosContract;
use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;

/**
 * [Reprogramación y Cancelación] RF-001 / RF-002 / RF-009 / RF-014.
 *
 * Datos del turno y catálogos necesarios para los modales. Sin datos
 * clínicos ni de contacto.
 */
final class GetTurnoCambio
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly DirectoriosAgenda $nombres,
        private readonly OrigenTurnosContract $origenes,
        private readonly MotivoTurnoResolver $motivos,
        private readonly ProfessionalDirectoryContract $profesionales,
        private readonly SalaDirectoryContract $salas,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $turnoId, User $actor): array
    {
        $turno = Turno::query()->find($turnoId);

        if ($turno === null) {
            throw new TurnoNotFoundException($turnoId);
        }

        $this->access->assertSucursal($actor, (int) $turno->sucursal_id);

        $origen = null;

        if ($turno->paquete_sesiones_id !== null) {
            $resumen = $this->origenes->obtener('paquete_sesiones', (int) $turno->paquete_sesiones_id);
            $origen = $resumen === null ? null : [
                'tipo' => 'paquete_sesiones',
                'id' => $resumen->id,
                'fecha_inicio' => $resumen->fechaInicio,
                'fecha_fin' => $resumen->fechaFin,
                'estado_efectivo' => $resumen->estadoEfectivo,
            ];
        } elseif ($turno->inscripcion_sala_id !== null) {
            $resumen = $this->origenes->obtener('inscripcion_sala', (int) $turno->inscripcion_sala_id);
            $origen = $resumen === null ? null : [
                'tipo' => 'inscripcion_sala',
                'id' => $resumen->id,
                'fecha_inicio' => $resumen->fechaInicio,
                'fecha_fin' => $resumen->fechaFin,
                'estado_efectivo' => $resumen->estadoEfectivo,
            ];
        }

        return [
            'turno' => [
                'id' => (int) $turno->id,
                'fecha' => $turno->fecha->toDateString(),
                'hora_inicio' => (string) $turno->hora_inicio,
                'hora_fin' => (string) $turno->hora_fin,
                'duracion_minutos' => (int) $turno->duracion_minutos,
                'estado' => $turno->estado->value,
                'estado_label' => $turno->estado->label(),
                'turno_origen_id' => $turno->turno_origen_id === null ? null : (int) $turno->turno_origen_id,
                'paciente' => [
                    'id' => (int) $turno->paciente_id,
                    'nombre' => $this->nombres->paciente((int) $turno->paciente_id),
                ],
                'servicio' => [
                    'id' => (int) $turno->servicio_id,
                    'nombre' => $this->nombres->servicio((int) $turno->servicio_id),
                ],
                'sala' => $turno->sala_id === null ? null : [
                    'id' => (int) $turno->sala_id,
                    'nombre' => $this->nombres->sala((int) $turno->sala_id),
                ],
                'profesional' => $turno->profesional_id === null ? null : [
                    'id' => (int) $turno->profesional_id,
                    'nombre' => $this->nombres->profesional((int) $turno->profesional_id),
                ],
            ],
            'origen' => $origen,
            'profesionales' => $this->mapa($this->profesionales->nombresPorSucursal((int) $turno->sucursal_id)),
            'salas' => $this->mapa($this->salas->nombresPorSucursal((int) $turno->sucursal_id)),
            'motivos' => [
                'reprogramar' => array_map(
                    static fn ($motivo): array => [
                        'id' => $motivo->id,
                        'codigo' => $motivo->codigo,
                        'nombre' => $motivo->nombre,
                        'origen' => $motivo->origen?->value,
                        'descuenta_saldo' => $motivo->descuentaSaldo,
                        'requiere_texto' => $motivo->requiereTexto,
                    ],
                    $this->motivos->paraAccion('reprogramar'),
                ),
                'cancelar' => array_map(
                    static fn ($motivo): array => [
                        'id' => $motivo->id,
                        'codigo' => $motivo->codigo,
                        'nombre' => $motivo->nombre,
                        'origen' => $motivo->origen?->value,
                        'descuenta_saldo' => $motivo->descuentaSaldo,
                        'requiere_texto' => $motivo->requiereTexto,
                    ],
                    $this->motivos->paraAccion('cancelar'),
                ),
            ],
            'can' => [
                'reprogramar' => $actor->can('turno.reprogramar'),
                'cancelar' => $actor->can('turno.cancelar'),
                'historial' => $actor->can('turno.historial'),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $items
     * @return list<array{id: int, nombre: string}>
     */
    private function mapa(array $items): array
    {
        return array_map(
            static fn (int $id, string $nombre): array => ['id' => $id, 'nombre' => $nombre],
            array_keys($items),
            array_values($items),
        );
    }
}
