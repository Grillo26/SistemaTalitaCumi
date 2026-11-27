<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MotivoBajaInvalidoException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'MOTIVO_BAJA_INVALIDO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                'motivo' => [$this->getMessage()],
            ],
        ], $this->status());
    }
}
