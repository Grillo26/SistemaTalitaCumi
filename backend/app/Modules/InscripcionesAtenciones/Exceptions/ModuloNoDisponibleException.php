<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

/**
 * Stub de un módulo que todavía no existe.
 *
 * Extiende LogicException para cumplir el contrato de stubs y expone un
 * render HTTP 503 para que la UI muestre "No disponible" sin romper la ficha.
 */
final class ModuloNoDisponibleException extends LogicException
{
    public function __construct(
        public readonly string $modulo,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : "Módulo {$modulo} no implementado todavía.");
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'MODULO_NO_DISPONIBLE',
            'modulo' => $this->modulo,
        ], 503);
    }
}
