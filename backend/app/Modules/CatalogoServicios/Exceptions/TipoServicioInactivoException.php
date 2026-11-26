<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TipoServicioInactivoException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $tipoServicioId)
    {
        parent::__construct('El tipo de servicio está inactivo.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TIPO_SERVICIO_INACTIVO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['tipo_servicio_id' => [$this->getMessage()]],
        ], $this->status());
    }
}
