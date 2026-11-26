<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Base de las excepciones de dominio del módulo 06.
 */
abstract class InscripcionesAtencionesDomainException extends RuntimeException
{
    abstract public function status(): int;

    abstract public function errorCode(): string;

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
        ], $this->status());
    }
}
