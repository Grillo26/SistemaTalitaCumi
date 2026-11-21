<?php

namespace App\Modules\Seguridad\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * El rol Superadmin no puede perder permisos críticos (02/RF-R08 / 02/RN-16).
 */
final class CriticalPermissionProtectedException extends SeguridadDomainException
{
    /**
     * @param  list<string>  $missing
     */
    public function __construct(private readonly array $missing)
    {
        parent::__construct(sprintf(
            'No se pueden quitar permisos críticos al rol Superadmin: %s.',
            implode(', ', $missing),
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CRITICAL_PERMISSION_PROTECTED';
    }

    /**
     * @return list<string>
     */
    public function missing(): array
    {
        return $this->missing;
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
