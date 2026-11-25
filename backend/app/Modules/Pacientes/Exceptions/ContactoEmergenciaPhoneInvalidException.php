<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContactoEmergenciaPhoneInvalidException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El teléfono del contacto de emergencia debe tener 8 dígitos y comenzar con 2, 3, 4, 6 o 7.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'CONTACT_EMERGENCY_PHONE_INVALID';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['telefono' => [$this->getMessage()]],
        ], $this->status());
    }
}
