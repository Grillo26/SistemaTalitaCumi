<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfesionalesInsuficientesException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  list<array{especialidad_id: int, nombre: string, requeridos: int, asignados: int}>  $faltantes
     */
    public function __construct(public readonly array $faltantes = [])
    {
        parent::__construct('Faltan profesionales requeridos por el tipo.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PROFESIONALES_INSUFICIENTES';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'faltantes' => $this->faltantes,
            'errors' => ['profesionales' => [$this->getMessage()]],
        ], $this->status());
    }
}
