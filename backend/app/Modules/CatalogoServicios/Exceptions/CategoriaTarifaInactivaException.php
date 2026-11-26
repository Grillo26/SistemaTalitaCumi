<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoriaTarifaInactivaException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $categoriaTarifaId)
    {
        parent::__construct('La categoría de tarifa está inactiva.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CATEGORIA_TARIFA_INACTIVA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['categoria_tarifa_id' => [$this->getMessage()]],
        ], $this->status());
    }
}
