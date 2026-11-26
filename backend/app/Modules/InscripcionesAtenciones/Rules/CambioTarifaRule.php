<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\CambioTarifaSinConfirmarException;

/**
 * [Vigencia y Renovación] RF-006 / RN-005 / CA-005.
 *
 * Compara la tarifa anterior (snapshot) contra la tarifa resuelta para la
 * nueva fecha. El monto visto por el usuario se compara siempre en backend.
 */
final class CambioTarifaRule
{
    public static function hayCambio(
        ?int $tarifaAnteriorId,
        string $montoAnterior,
        ?int $tarifaVigenteId,
        ?string $montoVigente,
    ): bool {
        if ($tarifaVigenteId === null || $montoVigente === null) {
            return false;
        }

        if ($tarifaAnteriorId !== null && $tarifaAnteriorId !== $tarifaVigenteId) {
            return true;
        }

        return bccomp($montoAnterior, $montoVigente, 2) !== 0;
    }

    /**
     * @param  array<string, mixed>  $tarifaVigente
     */
    public static function assertMontoVisto(
        string $montoVisto,
        string $montoVigente,
        bool $confirmado,
        bool $cambio,
        array $tarifaVigente = [],
    ): void {
        if (bccomp($montoVisto, $montoVigente, 2) !== 0) {
            throw new CambioTarifaSinConfirmarException(
                $tarifaVigente,
                $montoVisto,
                'La tarifa vigente cambió. Revisá el monto actual antes de confirmar.',
            );
        }

        if ($cambio && ! $confirmado) {
            throw new CambioTarifaSinConfirmarException(
                $tarifaVigente,
                $montoVisto,
                'La tarifa cambió respecto de la anterior. Confirmá explícitamente para continuar.',
            );
        }
    }
}
