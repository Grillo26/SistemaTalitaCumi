<?php

namespace App\Modules\AgendaTurnos\Queries\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\CancelacionMasivaRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosContract;

/**
 * [Reprogramación y Cancelación] RF-011 / RN-009 / CA-007 / CU-03.
 *
 * Orígenes del paciente con turnos futuros, con conteos de elegibles y
 * excluidos, para que Administración seleccione qué cancelar.
 */
final class GetOrigenesCancelables
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly OrigenTurnosContract $origenes,
        private readonly CancelacionMasivaRule $regla,
        private readonly DirectoriosAgenda $nombres,
        private readonly AgendaClock $clock,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(int $pacienteId, User $actor): array
    {
        $accesibles = $this->access->accesibles($actor);

        $turnos = Turno::query()
            ->where('paciente_id', $pacienteId)
            ->futuros($this->clock->hoy(), $this->clock->horaActual())
            ->whereIn('sucursal_id', $accesibles)
            ->where(static function ($query): void {
                $query->whereNotNull('paquete_sesiones_id')
                    ->orWhereNotNull('inscripcion_sala_id');
            })
            ->ordenado()
            ->get();

        $grupos = [];

        foreach ($turnos as $turno) {
            $tipo = $turno->paquete_sesiones_id !== null ? 'paquete_sesiones' : 'inscripcion_sala';
            $id = $turno->paquete_sesiones_id !== null
                ? (int) $turno->paquete_sesiones_id
                : (int) $turno->inscripcion_sala_id;
            $key = "{$tipo}:{$id}";
            $grupos[$key]['tipo'] = $tipo;
            $grupos[$key]['id'] = $id;
            $grupos[$key]['turnos'][] = TurnoExistente::fromTurno($turno);
        }

        $resultado = [];

        foreach ($grupos as $grupo) {
            $clasificacion = $this->regla->evaluar(
                $grupo['turnos'],
                $this->clock->hoy(),
                $this->clock->horaActual(),
            );
            $origen = $this->origenes->obtener($grupo['tipo'], $grupo['id']);
            $primero = $grupo['turnos'][0];

            $resultado[] = [
                'tipo' => $grupo['tipo'],
                'id' => $grupo['id'],
                'servicio' => [
                    'id' => $primero->servicioId,
                    'nombre' => $this->nombres->servicio($primero->servicioId),
                ],
                'sala' => $primero->salaId === null ? null : [
                    'id' => $primero->salaId,
                    'nombre' => $this->nombres->sala($primero->salaId),
                ],
                'fecha_inicio' => $origen?->fechaInicio,
                'fecha_fin' => $origen?->fechaFin,
                'estado_efectivo' => $origen?->estadoEfectivo,
                'total_turnos_futuros' => count($grupo['turnos']),
                'elegibles' => $clasificacion->cantidadElegibles(),
                'excluidos' => $clasificacion->cantidadExcluidos(),
            ];
        }

        return $resultado;
    }
}
