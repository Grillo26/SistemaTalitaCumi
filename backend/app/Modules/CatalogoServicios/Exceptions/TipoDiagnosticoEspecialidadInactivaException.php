<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TipoDiagnosticoEspecialidadInactivaException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $especialidadId)
    {
        parent::__construct('La especialidad seleccionada no existe o está inactiva.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TIPO_DIAGNOSTICO_ESPECIALIDAD_INACTIVA';
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
