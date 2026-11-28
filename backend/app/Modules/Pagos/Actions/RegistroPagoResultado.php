<?php

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Contracts\PayableEntityResumen;
use App\Modules\Pagos\Models\Pago;
use Illuminate\Support\Collection;

/**
 * Resultado de un registro de pago (o de su reintento idempotente).
 */
final readonly class RegistroPagoResultado
{
    /**
     * @param  Collection<int, Pago>  $pagos
     */
    public function __construct(
        public string $grupoPagoId,
        public Collection $pagos,
        public PayableEntityResumen $entidad,
        public string $saldoResultante,
        public bool $replay,
    ) {}
}
