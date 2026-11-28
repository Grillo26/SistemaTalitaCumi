<?php

namespace App\Modules\Pagos\Contracts;

final class UnavailableComprobantePago implements ComprobantePagoContract
{
    public function disponible(): bool
    {
        return false;
    }
}
