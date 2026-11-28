<?php

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Exceptions\PagoDeleteNotAllowedException;

/**
 * RN-006 / RNF-005: los pagos no se eliminan. Se anulan en 8.4.
 */
final class RejectPagoDeletionAction
{
    public function __invoke(int $id): never
    {
        throw new PagoDeleteNotAllowedException;
    }
}
