<?php

namespace App\Modules\Seguridad\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * La nueva contraseña no cumple la política vigente (RF-A02 / RN-12).
 */
final class PasswordPolicyViolationException extends SeguridadDomainException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('La contraseña no cumple la política vigente.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PASSWORD_POLICY_VIOLATION';
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                'password' => $this->errors,
            ],
        ], 422);
    }
}
