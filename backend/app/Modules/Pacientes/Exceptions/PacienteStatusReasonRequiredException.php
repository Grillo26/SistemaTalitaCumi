<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PacienteStatusReasonRequiredException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El motivo es obligatorio para este cambio de estado del paciente.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'STATUS_REASON_REQUIRED';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['motivo' => [$this->getMessage()]],
        ], $this->status());
    }
}
