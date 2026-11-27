<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\PorcentajeAsistenciaRule;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosResumen;
use Throwable;

/**
 * 07.04/RF-009, 07.04/RF-013, 07.04/RF-015, 07.04/CA-008.
 *
 * Efecto aproximado en los indicadores del origen del turno. No escribe.
 * PROPUESTO - VALIDAR: asistidos / (asistidos + faltos), cancelados fuera.
 */
final class IndicadorAsistenciaResolver
{
    public function __construct(
        private readonly PorcentajeAsistenciaRule $porcentaje,
        private readonly AsistenciaClock $clock,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function paraTurno(
        Turno $turno,
        EstadoTurno $destino,
        bool $correccion,
        ?OrigenTurnosResumen $origen,
    ): array {
        try {
            [$desde, $hasta] = $this->periodo($turno, $origen);
            $query = Turno::query()
                ->where('paciente_id', $turno->paciente_id)
                ->whereBetween('fecha', [$desde, $hasta]);

            if ($turno->paquete_sesiones_id !== null) {
                $query->where('paquete_sesiones_id', $turno->paquete_sesiones_id);
            } elseif ($turno->inscripcion_sala_id !== null) {
                $query->where('inscripcion_sala_id', $turno->inscripcion_sala_id);
            } else {
                $query->where('sucursal_id', $turno->sucursal_id);
            }

            $asistidos = (int) (clone $query)->where('estado', EstadoTurno::ASISTIO->value)->count();
            $faltos = (int) (clone $query)->where('estado', EstadoTurno::FALTO->value)->count();
            $cancelados = (int) (clone $query)->where('estado', EstadoTurno::CANCELADO->value)->count();
        } catch (Throwable) {
            return [
                'disponible' => false,
                'mensaje' => 'Efecto en indicadores no disponible.',
            ];
        }

        $resultantes = $this->aplicar($turno->estado, $destino, $correccion, $asistidos, $faltos);

        return [
            'disponible' => true,
            'periodo_desde' => $desde,
            'periodo_hasta' => $hasta,
            'asistidos' => $asistidos,
            'faltos' => $faltos,
            'cancelados' => $cancelados,
            'porcentaje_actual' => $this->porcentaje->calcular($asistidos, $faltos),
            'porcentaje_resultante' => $this->porcentaje->calcular(
                $resultantes['asistidos'],
                $resultantes['faltos'],
            ),
            'mensaje' => 'El porcentaje de asistencia se recalculará al guardar.',
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function periodo(Turno $turno, ?OrigenTurnosResumen $origen): array
    {
        if ($origen !== null) {
            return [$origen->fechaInicio, $origen->fechaFin];
        }

        $hoy = $this->clock->ahora();

        return [
            $hoy->startOfMonth()->toDateString(),
            $hoy->endOfMonth()->toDateString(),
        ];
    }

    /**
     * @return array{asistidos: int, faltos: int}
     */
    private function aplicar(
        EstadoTurno $actual,
        EstadoTurno $destino,
        bool $correccion,
        int $asistidos,
        int $faltos,
    ): array {
        if (! $correccion) {
            return $destino === EstadoTurno::ASISTIO
                ? ['asistidos' => $asistidos + 1, 'faltos' => $faltos]
                : ['asistidos' => $asistidos, 'faltos' => $faltos + 1];
        }

        if ($actual === EstadoTurno::ASISTIO && $destino === EstadoTurno::FALTO) {
            return ['asistidos' => max(0, $asistidos - 1), 'faltos' => $faltos + 1];
        }

        if ($actual === EstadoTurno::FALTO && $destino === EstadoTurno::ASISTIO) {
            return ['asistidos' => $asistidos + 1, 'faltos' => max(0, $faltos - 1)];
        }

        return ['asistidos' => $asistidos, 'faltos' => $faltos];
    }
}
