<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PagoPaqueteContract;

/**
 * Estado de pago en endpoint separado de la ficha (RF-016).
 */
final class GetPagoPaqueteQuery
{
    public function __construct(
        private readonly GetPaqueteQuery $detalle,
        private readonly PagoPaqueteContract $pagos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $id, User $actor): array
    {
        ($this->detalle)($id, $actor);

        return $this->pagos->estadoPago($id)->toArray();
    }
}
