<?php

namespace App\Modules\AgendaTurnos\Rules;

use DateTimeImmutable;

/**
 * [Reprogramación y Cancelación] RF-001 / RN-005.
 *
 * La nueva fecha/hora debe ser futura en La Paz, válida y estar dentro del
 * período de la entidad origen. La duración se hereda del turno original.
 */
final class NuevaFechaRule
{
    /**
     * @return list<string>
     */
    public function errores(
        string $fecha,
        string $horaInicio,
        string $hoy,
        string $horaActual,
        ?string $periodoInicio,
        ?string $periodoFin,
        int $duracionMinutos,
    ): array {
        $errores = [];
        $nueva = $this->fecha($fecha);

        if ($nueva === null) {
            return ['La nueva fecha no es válida.'];
        }

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horaInicio) !== 1) {
            return ['La nueva hora de inicio no es válida.'];
        }

        if ($duracionMinutos <= 0) {
            return ['La duración del turno no es válida.'];
        }

        if ($this->minutos($horaInicio) + $duracionMinutos > 24 * 60) {
            $errores[] = 'El turno no puede terminar después de las 24:00.';
        }

        if ($fecha < $hoy || ($fecha === $hoy && $horaInicio <= $horaActual)) {
            $errores[] = 'La nueva fecha y hora deben ser futuras.';
        }

        if ($periodoInicio !== null && $fecha < $periodoInicio) {
            $errores[] = 'La nueva fecha es anterior al inicio de la vigencia de la entidad origen.';
        }

        if ($periodoFin !== null && $fecha > $periodoFin) {
            $errores[] = 'La nueva fecha supera el fin de la vigencia de la entidad origen.';
        }

        return $errores;
    }

    public function esMismoSlot(
        string $nuevaFecha,
        string $nuevaHora,
        string $fechaOriginal,
        string $horaOriginal,
    ): bool {
        return $nuevaFecha === $fechaOriginal && $nuevaHora === $horaOriginal;
    }

    public function horaFin(string $horaInicio, int $duracionMinutos): string
    {
        $minutos = $this->minutos($horaInicio) + $duracionMinutos;

        return sprintf('%02d:%02d', intdiv($minutos, 60) % 24, $minutos % 60);
    }

    private function fecha(string $fecha): ?DateTimeImmutable
    {
        $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        if ($valor === false || $valor->format('Y-m-d') !== $fecha) {
            return null;
        }

        return $valor;
    }

    private function minutos(string $hora): int
    {
        $partes = explode(':', $hora);

        return ((int) ($partes[0] ?? 0)) * 60 + ((int) ($partes[1] ?? 0));
    }
}
