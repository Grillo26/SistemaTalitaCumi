<?php

namespace App\Modules\Pagos\Support;

use App\Models\User;
use App\Modules\Pagos\Contracts\ComprobantePagoContract;
use App\Modules\Pagos\Contracts\PayableEntityContract;

/**
 * Banderas `can` para la UI. Solo ocultan acciones; el backend revalida.
 */
final class PagoAbilities
{
    /**
     * @return array<string, bool>
     */
    public static function for(User $user): array
    {
        return [
            'view' => $user->can(PagoPermission::VIEW),
            'register' => $user->can(PagoPermission::REGISTER),
            'authorize_future' => $user->can(PagoPermission::AUTHORIZE_FUTURE),
            'export' => $user->can(PagoPermission::EXPORT),
            'delete' => false,
            'reprint' => app(ComprobantePagoContract::class)->disponible(),
            'payable_entities' => app(PayableEntityContract::class)->disponible(),
        ];
    }
}
