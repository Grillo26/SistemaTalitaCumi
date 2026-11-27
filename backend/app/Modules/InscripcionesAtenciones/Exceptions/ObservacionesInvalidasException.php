<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ObservacionesInvalidasException extends InscripcionesAtencionesDomainException
{
    public function __construct()
    {
        parent::__construct('Las observaciones no pueden superar los 500 caracteres.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'OBSERVACIONES_INVALIDAS';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                'observaciones' => [$this->getMessage()],
            ],
        ], $this->status());
    }
}
