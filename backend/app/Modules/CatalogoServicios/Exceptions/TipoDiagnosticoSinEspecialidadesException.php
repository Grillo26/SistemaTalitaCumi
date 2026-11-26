<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TipoDiagnosticoSinEspecialidadesException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('El tipo de diagnóstico requiere al menos una especialidad.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TIPO_DIAGNOSTICO_SIN_ESPECIALIDADES';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['especialidades' => [$this->getMessage()]],
        ], $this->status());
    }
}
