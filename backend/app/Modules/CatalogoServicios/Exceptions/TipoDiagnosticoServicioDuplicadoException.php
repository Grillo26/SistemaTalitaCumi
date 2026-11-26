<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TipoDiagnosticoServicioDuplicadoException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly string $servicioNombre)
    {
        parent::__construct('Ese servicio ya tiene un tipo de diagnóstico asociado.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TIPO_DIAGNOSTICO_SERVICIO_DUPLICADO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['servicio_id' => [$this->getMessage()]],
            'servicio' => ['nombre' => $this->servicioNombre],
        ], $this->status());
    }
}
