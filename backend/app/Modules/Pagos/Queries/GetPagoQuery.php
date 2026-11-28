<?php

namespace App\Modules\Pagos\Queries;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Pagos\Exceptions\PagoNotFoundException;
use App\Modules\Pagos\Models\Pago;
use Illuminate\Support\Collection;

/**
 * Detalle de un pago y de las líneas de su mismo grupo (08.01/RF-009).
 *
 * Fuera de alcance responde 404 sin revelar que existe.
 */
final class GetPagoQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @return array{pago: Pago, grupo: Collection<int, Pago>}
     */
    public function __invoke(int $id, User $actor): array
    {
        $accesibles = $this->access->accesibles($actor->id);

        $pago = $accesibles === []
            ? null
            : Pago::query()->porSucursales($accesibles)->whereKey($id)->first();

        if ($pago === null) {
            throw new PagoNotFoundException;
        }

        $grupo = Pago::query()
            ->where('grupo_pago_id', $pago->grupo_pago_id)
            ->orderBy('id')
            ->get();

        return ['pago' => $pago, 'grupo' => $grupo];
    }
}
