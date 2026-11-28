<?php

namespace App\Modules\Pagos\Contracts;

/**
 * Disponibilidad del comprobante imprimible (08.02, RF-011).
 *
 * Mientras 8.2 no exista, `disponible()` es false y la UI muestra el bloque
 * "no disponible" sin botón de reimpresión.
 */
interface ComprobantePagoContract
{
    public function disponible(): bool;
}
