<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Base de las excepciones de dominio del módulo 07.
 */
abstract class AgendaTurnosDomainException extends RuntimeException
{
    abstract public function status(): int;

    abstract public function errorCode(): string;

    /**
     * Datos adicionales para el cliente (por ejemplo, el detalle actualizado
     * de un conflicto detectado entre la previsualización y la confirmación).
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }

    public function render(Request $request): JsonResponse
    {
        $payload = [
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
        ];

        if ($this->context() !== []) {
            $payload['context'] = $this->context();
        }

        return response()->json($payload, $this->status());
    }
}
