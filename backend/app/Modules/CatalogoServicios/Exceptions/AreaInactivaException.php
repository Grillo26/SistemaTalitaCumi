<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AreaInactivaException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly string $areaNombre)
    {
        parent::__construct('El área está inactiva. Activá el área antes de operar con sus servicios.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'AREA_INACTIVA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['area_id' => [$this->getMessage()]],
        ], $this->status());
    }
}
