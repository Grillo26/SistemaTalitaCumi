<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Uso histórico de una tarifa (RF-T13 / RN-T09).
 *
 * Se implementará cuando existan inscripciones (06) y pagos (08).
 */
interface TarifaUsageContract
{
    public function hasInscripcionesOrPagos(int $tarifaId): bool;
}
