<?php

namespace App\Modules\Profesionales\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfesionalEspecialidadInvalidException extends ProfesionalesDomainException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_ESPECIALIDAD_INVALID';
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
