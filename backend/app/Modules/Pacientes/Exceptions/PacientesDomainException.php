<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Base de las excepciones de dominio del módulo 04.
 */
abstract class PacientesDomainException extends RuntimeException
{
    abstract public function status(): int;

    abstract public function errorCode(): string;

    /**
     * @return array<string, mixed>
     */
    protected function errorContext(): array
    {
        return [];
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(array_merge([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
        ], $this->errorContext()), $this->status());
    }
}
