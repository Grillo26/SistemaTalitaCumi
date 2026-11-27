<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteBatchContract;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Rules\SaldoSesionesRule;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Adjunta el saldo a las filas de un paquete sin romper el panel si Agenda
 * no está disponible. El contrato es batch para evitar N+1.
 */
final class VigenciaSaldoResolver
{
    public function __construct(private readonly ConsumoPaqueteBatchContract $consumo) {}

    /**
     * @param  Collection<int, object>  $rows
     */
    public function enrich(Collection $rows): void
    {
        $paqueteIds = [];

        foreach ($rows as $row) {
            $row->saldo = null;
            $row->saldo_disponible = false;

            if (($row->tipo_entidad ?? null) === TipoEntidadVigencia::PAQUETE_SESIONES->value) {
                $paqueteIds[] = (int) $row->id;
            }
        }

        if ($paqueteIds === [] || ! $this->consumo->disponible()) {
            return;
        }

        try {
            $resumenes = $this->consumo->resumenes(array_values(array_unique($paqueteIds)));
        } catch (Throwable) {
            return;
        }

        foreach ($rows as $row) {
            if (($row->tipo_entidad ?? null) !== TipoEntidadVigencia::PAQUETE_SESIONES->value) {
                continue;
            }

            $resumen = $resumenes[(int) $row->id] ?? null;

            if ($resumen === null) {
                continue;
            }

            $row->saldo = SaldoSesionesRule::calcular(
                (int) $row->cantidad_sesiones,
                (int) $resumen->realizadas,
                (int) $resumen->faltadas,
            );
            $row->saldo_disponible = true;
        }
    }
}
