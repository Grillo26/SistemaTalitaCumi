<?php

namespace App\Modules\Organizacion\Rules;

/**
 * Coherencia entre parámetros de agenda (RF-011 / RN-005 / CA-003).
 *
 * Recibe únicamente datos: los valores vigentes ya fusionados con el valor
 * propuesto. No consulta la base de datos.
 */
final class ParameterCoherenceRule
{
    /**
     * @return list<string>
     */
    public static function agendaKeys(): array
    {
        return [
            'duracion_sesion_minutos',
            'agenda.horario_apertura',
            'agenda.horario_cierre',
            'agenda.turno_manana.inicio',
            'agenda.turno_manana.fin',
            'agenda.turno_tarde.inicio',
            'agenda.turno_tarde.fin',
        ];
    }

    public static function affectsAgenda(string $clave): bool
    {
        return in_array($clave, self::agendaKeys(), true);
    }

    /**
     * @param  array<string, string>  $values  clave => valor serializado vigente/propuesto
     * @return string|null Mensaje de error, o null si la combinación es coherente.
     */
    public static function check(array $values, string $changedKey, string $newValue): ?string
    {
        $values[$changedKey] = $newValue;

        $morning = self::blockError(
            $values['agenda.turno_manana.inicio'] ?? null,
            $values['agenda.turno_manana.fin'] ?? null,
            'mañana',
        );

        if ($morning !== null) {
            return $morning;
        }

        $afternoon = self::blockError(
            $values['agenda.turno_tarde.inicio'] ?? null,
            $values['agenda.turno_tarde.fin'] ?? null,
            'tarde',
        );

        if ($afternoon !== null) {
            return $afternoon;
        }

        $opening = $values['agenda.horario_apertura'] ?? null;
        $closing = $values['agenda.horario_cierre'] ?? null;

        if ($opening !== null && $closing !== null && self::minutes($closing) <= self::minutes($opening)) {
            return 'La hora de cierre debe ser posterior a la hora de apertura.';
        }

        if (! self::affectsSessionDuration($changedKey)) {
            return null;
        }

        $duration = $values['duracion_sesion_minutos'] ?? null;

        if ($duration === null || ! is_numeric($duration)) {
            return null;
        }

        $duration = (int) $duration;

        foreach ([
            'mañana' => [$values['agenda.turno_manana.inicio'] ?? null, $values['agenda.turno_manana.fin'] ?? null],
            'tarde' => [$values['agenda.turno_tarde.inicio'] ?? null, $values['agenda.turno_tarde.fin'] ?? null],
        ] as $label => [$start, $end]) {
            if ($start === null || $end === null) {
                continue;
            }

            $block = self::minutes($end) - self::minutes($start);

            if ($block > 0 && $duration > $block) {
                return "La duración de la sesión ({$duration} minutos) no puede superar el bloque de agenda del turno {$label} ({$block} minutos).";
            }
        }

        return null;
    }

    private static function blockError(?string $start, ?string $end, string $label): ?string
    {
        if ($start === null || $end === null) {
            return null;
        }

        if (self::minutes($end) <= self::minutes($start)) {
            return "La hora de fin del turno {$label} debe ser posterior a la hora de inicio.";
        }

        return null;
    }

    private static function affectsSessionDuration(string $changedKey): bool
    {
        return $changedKey === 'duracion_sesion_minutos'
            || str_starts_with($changedKey, 'agenda.turno_');
    }

    private static function minutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time, 2), 2, '0');

        return ((int) $hours * 60) + (int) $minutes;
    }
}
