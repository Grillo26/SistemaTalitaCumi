<?php

namespace App\Modules\Seguridad\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Un usuario activo debe tener al menos una sucursal (02/RF-U05 / 02/RN-03).
 */
final class UserBranchRequiredException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('El usuario debe tener al menos una sucursal activa.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'USER_BRANCH_REQUIRED';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                'sucursales' => [$this->getMessage()],
            ],
        ], $this->status());
    }
}
