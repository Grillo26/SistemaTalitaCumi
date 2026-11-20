<?php

namespace App\Modules\Organizacion\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

/**
 * Dependencia de otro módulo todavía no implementada.
 *
 * Es un LogicException a propósito: el sistema falla de forma explícita en
 * lugar de devolver datos falsos, null o "todas las sucursales".
 */
final class ModulePendingException extends LogicException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'MODULE_02_PENDING',
        ], 503);
    }
}
