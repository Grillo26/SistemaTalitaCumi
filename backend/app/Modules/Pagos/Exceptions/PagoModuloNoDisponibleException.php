<?php

namespace App\Modules\Pagos\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

/**
 * Módulo pendiente (precedente ModuloNoDisponibleException de 06).
 *
 * Responde 503 MODULO_NO_DISPONIBLE. Nunca crea ni devuelve datos falsos.
 */
final class PagoModuloNoDisponibleException extends LogicException
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
