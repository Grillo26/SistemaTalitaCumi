<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TarifaVigenciaInvalidaException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly string $field = 'vigente_hasta')
    {
        parent::__construct('La fecha de inicio es obligatoria y la fecha de fin no puede ser anterior al inicio.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TARIFA_VIGENCIA_INVALIDA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [$this->field => [$this->getMessage()]],
        ], $this->status());
    }
}
