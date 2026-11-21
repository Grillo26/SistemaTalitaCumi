<?php

namespace App\Modules\Profesionales\Exceptions;

use App\Modules\Profesionales\Contracts\ResultadoAsignacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfesionalNotAssignableException extends ProfesionalesDomainException
{
    public function __construct(private readonly ResultadoAsignacion $resultado)
    {
        parent::__construct($resultado->mensaje);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_NOT_ASSIGNABLE';
    }

    public function resultado(): ResultadoAsignacion
    {
        return $this->resultado;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'motivo_codigo' => $this->resultado->codigo,
            'motivo_mensaje' => $this->resultado->mensaje,
        ], $this->status());
    }
}
