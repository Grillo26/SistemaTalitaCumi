<?php

namespace App\Modules\Seguridad\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Falta el rol o el rol no existe en el catálogo (02/RF-U04 / 02/RN-02).
 */
final class UserRoleRequiredException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('Seleccioná un rol válido del catálogo del sistema.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'USER_ROLE_REQUIRED';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                'rol_id' => [$this->getMessage()],
            ],
        ], $this->status());
    }
}
