<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InscripcionDatosIncompletosException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  list<string>  $campos
     */
    public function __construct(public readonly array $campos)
    {
        parent::__construct('Faltan datos obligatorios para la inscripción: '.implode(', ', $campos).'.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'INSCRIPCION_DATOS_INCOMPLETOS';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['campos' => $this->campos],
        ], $this->status());
    }
}
