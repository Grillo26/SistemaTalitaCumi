<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TarifaMontoInvalidoException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('El monto debe ser un valor no negativo con hasta dos decimales.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TARIFA_MONTO_INVALIDO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['monto' => [$this->getMessage()]],
        ], $this->status());
    }
}
