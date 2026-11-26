<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServicioInactivoException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $servicioId)
    {
        parent::__construct('El servicio está inactivo y no puede usarse en nuevas operaciones.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'SERVICIO_INACTIVO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
        ], $this->status());
    }
}
