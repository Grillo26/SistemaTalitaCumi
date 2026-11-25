<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TutorPrincipalReasonRequiredException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El motivo es obligatorio para reemplazar al tutor principal.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TUTOR_PRINCIPAL_REASON_REQUIRED';
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
