<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PacienteSensitiveFieldsForbiddenException extends PacientesDomainException
{
    /**
     * @param  list<string>  $fields
     */
    public function __construct(private readonly array $fields)
    {
        parent::__construct('No tenés permiso para leer o modificar los datos sensibles del paciente.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'SENSITIVE_FIELDS_FORBIDDEN';
    }

    public function render(Request $request): JsonResponse
    {
        $errors = [];

        foreach ($this->fields as $field) {
            $errors[$field] = [$this->getMessage()];
        }

        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => $errors,
        ], $this->status());
    }
}
