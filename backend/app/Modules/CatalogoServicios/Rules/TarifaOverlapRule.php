<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TarifaSolapadaException;

/**
 * No-solapamiento de tarifas (RN-T04 / RF-T05).
 *
 * La Action ya filtró por servicio, sucursal y categoría, y excluyó la
 * propia tarifa al editar. La regla solo compara rangos inclusivos.
 */
final class TarifaOverlapRule
{
    /**
     * @param  list<array<string, mixed>>  $ranges
     */
    public static function assertNoOverlap(
        string $vigenteDesde,
        ?string $vigenteHasta,
        array $ranges,
    ): void {
        $candidateStart = $vigenteDesde;
        $candidateEnd = $vigenteHasta ?? '9999-12-31';

        foreach ($ranges as $range) {
            $existingStart = (string) $range['vigente_desde'];
            $existingEnd = $range['vigente_hasta'] !== null
                ? (string) $range['vigente_hasta']
                : '9999-12-31';

            if ($existingStart <= $candidateEnd && $candidateStart <= $existingEnd) {
                throw new TarifaSolapadaException([
                    'id' => $range['id'] ?? null,
                    'vigente_desde' => $existingStart,
                    'vigente_hasta' => $range['vigente_hasta'] ?? null,
                    'monto' => $range['monto'] ?? null,
                    'sucursal' => $range['sucursal'] ?? null,
                    'categoria' => $range['categoria'] ?? null,
                ]);
            }
        }
    }
}
