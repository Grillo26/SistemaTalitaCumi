<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcionSala;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InscripcionRetiradaException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly EstadoInscripcionSala $estado)
    {
        parent::__construct(
            'La inscripción no está activa y no puede modificarse, renovarse ni darse de baja nuevamente.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'INSCRIPCION_RETIRADA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
        ], $this->status());
    }
}
