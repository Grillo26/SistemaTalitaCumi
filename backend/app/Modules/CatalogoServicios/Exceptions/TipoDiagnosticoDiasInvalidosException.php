<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TipoDiagnosticoDiasInvalidosException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('Los días estimados deben ser un entero mayor o igual a 1.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TIPO_DIAGNOSTICO_DIAS_INVALIDOS';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['dias_estimados' => [$this->getMessage()]],
        ], $this->status());
    }
}
