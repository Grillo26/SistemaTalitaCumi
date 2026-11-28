<?php

namespace App\Modules\AgendaTurnos\Rules;

use DateTimeImmutable;

/**
 * 07.04/CU-03 FE-01, 07.04/RF-009.
 *
 * Rango de indicadores válido y acotado para proteger RNF-001/RNF-002.
 */
final class RangoIndicadoresRule
{
    public const MAX_DIAS = 366;

    /**
     * @return list<string>
     */
    public function errores(string $desde, string $hasta): array
    {
        $inicio = $this->fecha($desde);
        $fin = $this->fecha($hasta);

        if ($inicio === null || $fin === null) {
            return ['El período debe tener fechas válidas en formato YYYY-MM-DD.'];
        }

        if ($inicio > $fin) {
            return ['La fecha desde no puede ser posterior a la fecha hasta.'];
        }

        if ($inicio->diff($fin)->days > self::MAX_DIAS) {
            return ['El período no puede superar '.self::MAX_DIAS.' días.'];
        }

        return [];
    }

    private function fecha(string $fecha): ?DateTimeImmutable
    {
        $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        return $valor !== false && $valor->format('Y-m-d') === $fecha ? $valor : null;
    }
}
