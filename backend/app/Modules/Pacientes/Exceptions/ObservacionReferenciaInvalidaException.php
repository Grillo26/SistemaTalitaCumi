<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ObservacionReferenciaInvalidaException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('La observación de referencia no pertenece a esta ficha.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'OBSERVACION_REFERENCE_INVALID';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['observacion_referencia_id' => [$this->getMessage()]],
        ], $this->status());
    }
}
