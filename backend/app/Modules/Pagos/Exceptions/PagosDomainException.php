<?php

namespace App\Modules\Pagos\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Base de las excepciones de dominio del módulo 08.01.
 */
abstract class PagosDomainException extends RuntimeException
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
