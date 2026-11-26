<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AreaNombreDuplicadoException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('Ya existe un área con ese nombre.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'AREA_NOMBRE_DUPLICADO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['nombre' => [$this->getMessage()]],
        ], $this->status());
    }
}
