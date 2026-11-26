<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use App\Modules\InscripcionesAtenciones\Enums\EstadoPaqueteSesiones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaqueteRetiradoException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly EstadoPaqueteSesiones $estado)
    {
        parent::__construct(
            'El paquete está retirado y no puede modificarse, renovarse ni darse de baja nuevamente.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PAQUETE_RETIRADO';
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
