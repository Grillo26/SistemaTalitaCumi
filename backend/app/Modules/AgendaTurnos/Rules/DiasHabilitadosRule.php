<?php

namespace App\Modules\AgendaTurnos\Rules;

use Carbon\CarbonImmutable;

/**
 * 07.05/RF-002 / 07.05/RN-010.
 *
 * Valida que la fecha del turno caiga en un día habilitado por configuración
 * (`agenda.dias_semana_habilitados`, ISO 1=lunes … 7=domingo).
 */
final class DiasHabilitadosRule
{
    /**
     * @param  list<int>  $diasHabilitados
     */
    public function habilitado(string $fecha, array $diasHabilitados): bool
    {
        $dia = $this->fecha($fecha)?->isoWeekday();

        return $dia !== null && in_array($dia, $diasHabilitados, true);
    }

    /**
     * @param  list<int>  $diasHabilitados
     * @return list<string>
     */
    public function errores(string $fecha, array $diasHabilitados): array
    {
        $dia = $this->fecha($fecha)?->isoWeekday();

        if ($dia === null) {
            return ['La fecha del turno no es válida.'];
        }

        if ($diasHabilitados === []) {
            return ['No hay días habilitados configurados para la agenda.'];
        }

        if (! in_array($dia, $diasHabilitados, true)) {
            return ['La fecha elegida no corresponde a un día habilitado para la agenda.'];
        }

        return [];
    }

    private function fecha(string $fecha): ?CarbonImmutable
    {
        $valor = CarbonImmutable::createFromFormat('!Y-m-d', $fecha);

        if ($valor === false || $valor->format('Y-m-d') !== $fecha) {
            return null;
        }

        return $valor;
    }
}
