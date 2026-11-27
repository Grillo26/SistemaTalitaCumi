<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Support\FechaGenerada;
use DateTimeImmutable;

/**
 * [Programación de Turnos] RN-002 / RF-003 / RF-015 / CA-001 / CA-005.
 *
 * Genera las fechas del patrón dentro de [fecha_inicio, fecha_fin] inclusive.
 * Solo considera lunes a sábado, omite fechas pasadas y feriados, y aplica un
 * tope opcional por cantidad de sesiones pactadas. Regla pura: "hoy", la hora
 * actual y los feriados se inyectan.
 */
final class GeneradorFechasRule
{
    /**
     * @param  list<int|string>  $diasSemana
     * @param  array<string, string>  $feriados  fecha (Y-m-d) => descripción
     * @return list<FechaGenerada>
     */
    public function generar(
        array $diasSemana,
        string $fechaInicio,
        string $fechaFin,
        string $hoy,
        string $horaActual,
        string $horaInicio,
        ?int $cantidadMaxima = null,
        array $feriados = [],
    ): array {
        $dias = array_values(array_unique(array_filter(
            array_map(
                static fn (int|string $dia): int => (int) $dia,
                $diasSemana,
            ),
            static fn (int $dia): bool => $dia >= 1 && $dia <= 6,
        )));

        $inicio = $this->fecha($fechaInicio);
        $fin = $this->fecha($fechaFin);
        $limite = $this->fecha($hoy);

        if ($inicio === null || $fin === null || $limite === null || $inicio > $fin) {
            return [];
        }

        $resultado = [];
        $generables = 0;
        $cursor = $inicio;

        while ($cursor <= $fin) {
            $diaSemana = (int) $cursor->format('N');

            if (! in_array($diaSemana, $dias, true)) {
                $cursor = $cursor->modify('+1 day');

                continue;
            }

            $fecha = $cursor->format('Y-m-d');
            $estado = FechaGenerada::GENERABLE;
            $motivo = null;

            if (array_key_exists($fecha, $feriados)) {
                $estado = FechaGenerada::FERIADO;
                $descripcion = trim($feriados[$fecha]);
                $motivo = $descripcion === ''
                    ? 'La fecha es feriado.'
                    : "La fecha es feriado: {$descripcion}.";
            } elseif ($cursor < $limite) {
                $estado = FechaGenerada::PASADA;
                $motivo = 'La fecha ya pasó.';
            } elseif ($cursor == $limite && $horaInicio <= $horaActual) {
                $estado = FechaGenerada::PASADA;
                $motivo = 'La hora de inicio de hoy ya pasó.';
            } elseif ($cantidadMaxima !== null && $generables >= $cantidadMaxima) {
                $estado = FechaGenerada::OMITIDA;
                $motivo = 'Supera la cantidad de sesiones pactadas.';
            }

            if ($estado === FechaGenerada::GENERABLE) {
                $generables++;
            }

            $resultado[] = new FechaGenerada($fecha, $diaSemana, $estado, $motivo);
            $cursor = $cursor->modify('+1 day');
        }

        return $resultado;
    }

    private function fecha(string $fecha): ?DateTimeImmutable
    {
        $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        if ($valor === false || $valor->format('Y-m-d') !== $fecha) {
            return null;
        }

        return $valor;
    }
}
