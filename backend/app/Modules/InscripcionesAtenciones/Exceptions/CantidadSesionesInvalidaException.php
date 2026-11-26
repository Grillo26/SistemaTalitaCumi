<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CantidadSesionesInvalidaException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message = 'La cantidad de sesiones debe ser un entero mayor o igual a 1.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'CANTIDAD_SESIONES_INVALIDA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['cantidad_sesiones' => [$this->getMessage()]],
        ], $this->status());
    }
}
