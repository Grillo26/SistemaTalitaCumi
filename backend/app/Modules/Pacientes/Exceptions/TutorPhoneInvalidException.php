<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TutorPhoneInvalidException extends PacientesDomainException
{
    public function __construct(private readonly string $field = 'telefono')
    {
        parent::__construct($field === 'telefono_alternativo'
            ? 'El teléfono alternativo debe tener 8 dígitos y comenzar con 2, 3, 4, 6 o 7.'
            : 'El teléfono principal debe tener 8 dígitos y comenzar con 6 o 7.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TUTOR_PHONE_INVALID';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [$this->field => [$this->getMessage()]],
        ], $this->status());
    }
}
