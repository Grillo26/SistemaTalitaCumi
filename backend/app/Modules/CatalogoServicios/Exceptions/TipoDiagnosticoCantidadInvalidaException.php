<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TipoDiagnosticoCantidadInvalidaException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $especialidadId)
    {
        parent::__construct('La cantidad mínima debe ser un entero mayor o igual a 1.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TIPO_DIAGNOSTICO_CANTIDAD_INVALIDA';
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
