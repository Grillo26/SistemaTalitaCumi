<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\PorcentajeAsistenciaRule;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosContract;
use App\Modules\Pacientes\Contracts\AsistenciaResumen;
use App\Modules\Pacientes\Contracts\PatientAttendanceSummaryContract;

/**
 * 07.04/RF-015 / 04.04 — resumen de asistencia del paciente.
 *
 * PROPUESTO - VALIDAR: período de las entidades origen activas del paciente.
 * Si no hay ninguna, devuelve períodos null y 04.04 muestra “sin período
 * vigente”. Los cancelados no entran al porcentaje.
 */
final class EloquentPatientAttendanceSummary implements PatientAttendanceSummaryContract
{
    public function __construct(
        private readonly OrigenTurnosContract $origenes,
        private readonly PorcentajeAsistenciaRule $porcentaje,
    ) {}

    public function disponible(): bool
    {
        return true;
    }

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     */
    public function resumenAsistencia(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
        ?int $sucursalContexto = null,
    ): AsistenciaResumen {
        [$desde, $hasta] = $this->periodo(
            $pacienteId,
            $sucursalesAccesibles,
            $sucursalContexto,
        );

        if ($desde === null || $hasta === null) {
            return new AsistenciaResumen(0, 0, 0, 0.0, null, null);
        }

        $query = Turno::query()
            ->where('paciente_id', $pacienteId)
            ->whereBetween('fecha', [$desde, $hasta])
            ->where('estado', '!=', EstadoTurno::REPROGRAMADO->value);

        if ($sucursalesAccesibles !== null) {
            $query->whereIn('sucursal_id', $sucursalesAccesibles);
        }

        if ($sucursalContexto !== null) {
            $query->where('sucursal_id', $sucursalContexto);
        }

        $asistidos = (int) (clone $query)->where('estado', EstadoTurno::ASISTIO->value)->count();
        $falto = (int) (clone $query)->where('estado', EstadoTurno::FALTO->value)->count();
        $cancelados = (int) (clone $query)->where('estado', EstadoTurno::CANCELADO->value)->count();

        return new AsistenciaResumen(
            asistidos: $asistidos,
            falto: $falto,
            cancelados: $cancelados,
            porcentaje: $this->porcentaje->calcular($asistidos, $falto),
            periodoInicio: $desde,
            periodoFin: $hasta,
        );
    }

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     * @return array{0: ?string, 1: ?string}
     */
    private function periodo(
        int $pacienteId,
        ?array $sucursalesAccesibles,
        ?int $sucursalContexto,
    ): array {
        $base = Turno::query()
            ->where('paciente_id', $pacienteId)
            ->where('estado', '!=', EstadoTurno::REPROGRAMADO->value)
            ->where(static function ($sub): void {
                $sub->whereNotNull('paquete_sesiones_id')
                    ->orWhereNotNull('inscripcion_sala_id');
            });

        if ($sucursalesAccesibles !== null) {
            $base->whereIn('sucursal_id', $sucursalesAccesibles);
        }

        if ($sucursalContexto !== null) {
            $base->where('sucursal_id', $sucursalContexto);
        }

        $paquetes = (clone $base)
            ->whereNotNull('paquete_sesiones_id')
            ->distinct()
            ->pluck('paquete_sesiones_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $inscripciones = (clone $base)
            ->whereNotNull('inscripcion_sala_id')
            ->distinct()
            ->pluck('inscripcion_sala_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $desde = null;
        $hasta = null;

        foreach ($paquetes as $id) {
            $origen = $this->origenes->obtener('paquete_sesiones', $id);

            if ($origen === null || $origen->estadoEfectivo !== 'activa') {
                continue;
            }

            $desde = $desde === null || $origen->fechaInicio < $desde ? $origen->fechaInicio : $desde;
            $hasta = $hasta === null || $origen->fechaFin > $hasta ? $origen->fechaFin : $hasta;
        }

        foreach ($inscripciones as $id) {
            $origen = $this->origenes->obtener('inscripcion_sala', $id);

            if ($origen === null || $origen->estadoEfectivo !== 'activa') {
                continue;
            }

            $desde = $desde === null || $origen->fechaInicio < $desde ? $origen->fechaInicio : $desde;
            $hasta = $hasta === null || $origen->fechaFin > $hasta ? $origen->fechaFin : $hasta;
        }

        return [$desde, $hasta];
    }
}
