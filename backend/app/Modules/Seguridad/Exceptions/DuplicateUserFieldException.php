<?php

namespace App\Modules\Seguridad\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Documento, correo o nombre de usuario duplicado (02/RF-U02 / 02/RN-01).
 */
final class DuplicateUserFieldException extends SeguridadDomainException
{
    public function __construct(private readonly string $field)
    {
        $label = match ($field) {
            'email' => 'correo',
            'username' => 'nombre de usuario',
            'documento' => 'documento',
            default => $field,
        };

        parent::__construct("Ya existe un usuario con el mismo {$label}.");
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'USER_DUPLICATE_FIELD';
    }

    public function field(): string
    {
        return $this->field;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                $this->field => [$this->getMessage()],
            ],
        ], $this->status());
    }
}
