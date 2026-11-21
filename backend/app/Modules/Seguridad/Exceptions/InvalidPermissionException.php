<?php

namespace App\Modules\Seguridad\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Algún permiso enviado no existe o no pertenece al guard web (02/RF-R04).
 */
final class InvalidPermissionException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('Uno o más permisos seleccionados no son válidos.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'INVALID_PERMISSION';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                'permisos' => [$this->getMessage()],
            ],
        ], $this->status());
    }
}
