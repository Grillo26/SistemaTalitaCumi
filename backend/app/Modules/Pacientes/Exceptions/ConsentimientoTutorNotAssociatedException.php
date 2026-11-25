<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConsentimientoTutorNotAssociatedException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El tutor seleccionado no está asociado al paciente o está inactivo.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'CONSENT_TUTOR_NOT_ASSOCIATED';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['tutor_id' => [$this->getMessage()]],
        ], $this->status());
    }
}
