<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TipoDiagnosticoNombreDuplicadoException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('Ya existe un tipo de diagnóstico con ese nombre.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TIPO_DIAGNOSTICO_NOMBRE_DUPLICADO';
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
