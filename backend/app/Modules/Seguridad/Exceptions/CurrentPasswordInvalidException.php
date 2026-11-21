<?php

namespace App\Modules\Seguridad\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contraseña actual incorrecta al cambiar la propia contraseña (RF-A03).
 */
final class CurrentPasswordInvalidException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('La contraseña actual no es correcta.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'CURRENT_PASSWORD_INVALID';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                'current_password' => [$this->getMessage()],
            ],
        ], 422);
    }
}
