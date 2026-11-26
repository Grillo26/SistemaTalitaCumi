<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PagoPaqueteContract;

/**
 * Inicio del registro de pago de un paquete (RF-016).
 */
final class IniciarPagoPaqueteQuery
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

        return $this->pagos->iniciarRegistroPago($id)->toArray();
    }
}
