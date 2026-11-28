<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AsistenciaClock;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosResumen;
use Carbon\CarbonImmutable;

/**
 * 07.04/RF-001, 07.04/RF-008, 07.04/RN-003, 07.04/RN-005, 07.04/RN-007.
 *
 * Reglas puras de elegibilidad del turno. La Action y la Query cargan el
 * turno y el origen; esta clase no consulta la base de datos.
 */
final class TurnoAsistibleRule
{
    public function __construct(private readonly AsistenciaClock $clock) {}

    /**
     * @return list<string>
     */
    public function errores(
        Turno $turno,
        bool $tieneOrigen,
        ?OrigenTurnosResumen $origen,
        ?CarbonImmutable $ahora = null,
    ): array {
        $errores = [];
        $ahora ??= $this->clock->ahora();

        if ($turno->estado === EstadoTurno::CANCELADO) {
            return ['El turno está cancelado y no admite asistencia.'];
        }

        if ($turno->estado === EstadoTurno::REPROGRAMADO) {
            return ['El turno fue reprogramado; gestioná el turno vigente.'];
        }

        if (in_array($turno->estado, [EstadoTurno::ASISTIO, EstadoTurno::FALTO], true)) {
            return ['El turno ya tiene asistencia registrada.'];
        }

        if ($turno->estado !== EstadoTurno::PROGRAMADO) {
            return ['El turno no admite asistencia en su estado actual.'];
        }

        $inicio = $this->clock->inicioTurno($turno);

        if ($ahora->lessThan($inicio)) {
            $errores[] = 'El turno todavía no comenzó; no se puede registrar asistencia antes de su hora de inicio.';
        }

        if (! $tieneOrigen) {
            return $errores;
        }

        if ($origen === null) {
            return [...$errores, 'No se pudo verificar la entidad origen del turno.'];
        }

        $fechaTurno = $turno->fecha->toDateString();

        if ($fechaTurno < $origen->fechaInicio || $fechaTurno > $origen->fechaFin) {
            $errores[] = 'El turno está fuera del período de la entidad origen.';
        }

        if ($origen->tipo === 'inscripcion_sala') {
            if ($origen->estadoEfectivo !== 'activa') {
                $errores[] = 'La inscripción no está activa; no permite registrar asistencias.';
            }

            return $errores;
        }

        if ($origen->tipo === 'paquete_sesiones') {
            if (in_array($origen->estadoEfectivo, ['retirada', 'en_pausa'], true)) {
                $errores[] = 'El paquete no está disponible para registrar asistencia.';
            }

            if (! in_array($origen->estadoEfectivo, ['activa', 'vencida', 'renovada', 'retirada', 'en_pausa'], true)) {
                $errores[] = 'El estado del paquete no permite registrar asistencia.';
            }
        }

        return $errores;
    }

    public function permite(
        Turno $turno,
        bool $tieneOrigen,
        ?OrigenTurnosResumen $origen,
        ?CarbonImmutable $ahora = null,
    ): bool {
        return $this->errores($turno, $tieneOrigen, $origen, $ahora) === [];
    }
}
