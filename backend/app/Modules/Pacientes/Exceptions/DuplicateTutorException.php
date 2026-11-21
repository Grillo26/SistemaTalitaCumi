<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DuplicateTutorException extends PacientesDomainException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(private readonly array $errors = [])
    {
        parent::__construct('Ya existe un tutor con esos datos. Buscalo y asocialo al paciente en lugar de crearlo de nuevo.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TUTOR_DUPLICATE';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => $this->errors,
        ], $this->status());
    }
}
