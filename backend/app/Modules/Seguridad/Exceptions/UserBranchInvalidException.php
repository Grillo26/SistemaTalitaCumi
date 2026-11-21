<?php

namespace App\Modules\Seguridad\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Alguna sucursal asignada no existe o está inactiva (02/RF-U05).
 */
final class UserBranchInvalidException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('Todas las sucursales asignadas deben existir y estar activas.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'USER_BRANCH_INVALID';
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
