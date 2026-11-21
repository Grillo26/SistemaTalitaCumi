<?php

namespace App\Modules\Profesionales\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DuplicateEspecialidadNameException extends ProfesionalesDomainException
{
    public function __construct()
    {
        parent::__construct('Ya existe una especialidad con el mismo nombre.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ESPECIALIDAD_NAME_DUPLICATED';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['nombre' => [$this->getMessage()]],
        ], $this->status());
    }
}
