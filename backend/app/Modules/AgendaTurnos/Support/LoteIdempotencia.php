<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\AccionLoteGeneracion;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Exceptions\ClaveIdempotenciaReutilizadaException;
use App\Modules\AgendaTurnos\Models\TurnoLoteGeneracion;

/**
 * Resolución de la clave de idempotencia (RT-001).
 */
final class LoteIdempotencia
{
    public function buscar(string $clave): ?TurnoLoteGeneracion
    {
        return TurnoLoteGeneracion::query()
            ->where('clave_idempotencia', $clave)
            ->first();
    }

    public function asegurarCoincide(
        TurnoLoteGeneracion $lote,
        TipoOrigenTurno $tipo,
        int $origenId,
        AccionLoteGeneracion $accion,
    ): void {
        if (
            $lote->tipo_origen !== $tipo
            || (int) $lote->origen_id !== $origenId
            || $lote->accion !== $accion
        ) {
            throw new ClaveIdempotenciaReutilizadaException;
        }
    }
}
