<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use App\Modules\InscripcionesAtenciones\Enums\EstadoPaqueteSesiones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaqueteNoGestionableException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly EstadoPaqueteSesiones $estado)
    {
        parent::__construct(
            sprintf(
                'El paquete está en estado "%s" y no admite esta operación en este submódulo.',
                $estado->label(),
            ),
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PAQUETE_NO_GESTIONABLE';
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
