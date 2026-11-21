<?php

namespace App\Modules\Seguridad\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Falta el motivo de desactivación (02/RF-U09 / 02/RN-10).
 */
final class UserStatusReasonRequiredException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('El motivo es obligatorio para desactivar un usuario.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'USER_STATUS_REASON_REQUIRED';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                'motivo' => [$this->getMessage()],
            ],
        ], $this->status());
    }
}
