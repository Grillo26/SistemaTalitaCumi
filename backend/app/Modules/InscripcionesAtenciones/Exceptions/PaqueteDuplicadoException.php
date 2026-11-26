<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaqueteDuplicadoException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  array<string, mixed>  $conflict
     */
    public function __construct(public readonly array $conflict)
    {
        parent::__construct(
            'Ya existe un paquete activo del paciente para ese servicio con un período solapado.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PAQUETE_DUPLICADO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'conflict' => $this->conflict,
        ], $this->status());
    }
}
