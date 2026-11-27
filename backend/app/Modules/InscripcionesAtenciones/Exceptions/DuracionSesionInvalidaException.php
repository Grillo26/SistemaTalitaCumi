<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DuracionSesionInvalidaException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message = 'La duración de la sesión debe ser un entero mayor a cero.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'DURACION_SESION_INVALIDA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['duracion_minutos' => [$this->getMessage()]],
        ], $this->status());
    }
}
