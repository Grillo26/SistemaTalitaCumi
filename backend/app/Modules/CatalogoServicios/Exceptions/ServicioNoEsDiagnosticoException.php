<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServicioNoEsDiagnosticoException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly string $servicioNombre)
    {
        parent::__construct(
            'El servicio seleccionado no es del tipo Diagnóstico / Evaluación.',
        );
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'SERVICIO_NO_ES_DIAGNOSTICO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['servicio_id' => [$this->getMessage()]],
        ], $this->status());
    }
}
