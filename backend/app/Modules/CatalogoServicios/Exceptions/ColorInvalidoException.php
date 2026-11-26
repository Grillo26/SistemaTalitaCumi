<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ColorInvalidoException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('El color debe tener formato hexadecimal #RGB o #RRGGBB.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'COLOR_INVALIDO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['color' => [$this->getMessage()]],
        ], $this->status());
    }
}
