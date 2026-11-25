<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PacienteSearchTermTooShortException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Ingresá al menos 2 caracteres para buscar.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PACIENTE_SEARCH_TERM_TOO_SHORT';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['search' => [$this->getMessage()]],
        ], $this->status());
    }
}
