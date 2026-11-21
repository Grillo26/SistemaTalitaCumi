<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ObservacionDateInvalidException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('La fecha de la observación no puede ser futura.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'OBSERVACION_DATE_INVALID';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['fecha' => [$this->getMessage()]],
        ], $this->status());
    }
}
