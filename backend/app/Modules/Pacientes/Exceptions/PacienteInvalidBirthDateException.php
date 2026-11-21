<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PacienteInvalidBirthDateException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('La fecha de nacimiento no puede ser futura.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'INVALID_BIRTH_DATE';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['fecha_nacimiento' => [$this->getMessage()]],
        ], $this->status());
    }
}
