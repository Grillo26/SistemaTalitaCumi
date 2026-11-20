<?php

namespace App\Modules\Organizacion\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DuplicateTipoSalaException extends OrganizacionDomainException
{
    public function __construct()
    {
        parent::__construct('Ya existe un tipo de sala con ese nombre.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TIPO_SALA_DUPLICADO';
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
