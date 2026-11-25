<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DuplicateTutorException extends PacientesDomainException
{
    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly array $errors = [],
        private readonly array $data = [],
    ) {
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
        $body = [
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => $this->errors,
        ];

        if ($this->data !== []) {
            $body['data'] = $this->data;
        }

        return response()->json($body, $this->status());
    }
}
