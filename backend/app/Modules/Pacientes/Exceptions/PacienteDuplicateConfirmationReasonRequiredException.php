<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PacienteDuplicateConfirmationReasonRequiredException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Para confirmar que no es un duplicado tenés que ingresar un motivo.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'DUPLICATE_CONFIRMATION_REASON_REQUIRED';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['motivo_confirmacion' => [$this->getMessage()]],
        ], $this->status());
    }
}
