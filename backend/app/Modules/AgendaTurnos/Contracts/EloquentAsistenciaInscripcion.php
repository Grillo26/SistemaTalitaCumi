<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\InscripcionesAtenciones\Contracts\AsistenciaInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\AsistenciaInscripcionResumen;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosContract;

/**
 * 07.04/RF-014 / 06.01 — resumen de asistencia de una inscripción a sala.
 *
 * Cuenta turnos de la inscripción; los reprogramados no se suman porque su
 * reemplazo es el turno vigente. No expone datos clínicos.
 */
final class EloquentAsistenciaInscripcion implements AsistenciaInscripcionContract
{
    public function __construct(private readonly OrigenTurnosContract $origenes) {}

    public function disponible(): bool
    {
        return true;
    }

    public function resumen(int $inscripcionId): AsistenciaInscripcionResumen
    {
        $origen = $this->origenes->obtener('inscripcion_sala', $inscripcionId);

        $query = Turno::query()
            ->where('inscripcion_sala_id', $inscripcionId)
            ->where('estado', '!=', EstadoTurno::REPROGRAMADO->value);

        if ($origen !== null) {
            $query->whereBetween('fecha', [$origen->fechaInicio, $origen->fechaFin]);
        }

        $asistencias = (int) (clone $query)->where('estado', EstadoTurno::ASISTIO->value)->count();
        $faltas = (int) (clone $query)->where('estado', EstadoTurno::FALTO->value)->count();
        $cancelados = (int) (clone $query)->where('estado', EstadoTurno::CANCELADO->value)->count();
        $total = (int) $query->count();

        return new AsistenciaInscripcionResumen(
            asistencias: $asistencias,
            faltas: $faltas,
            cancelados: $cancelados,
            total: $total,
            periodoDesde: $origen?->fechaInicio,
            periodoHasta: $origen?->fechaFin,
        );
    }
}
