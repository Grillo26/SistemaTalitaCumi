<?php

namespace App\Modules\Organizacion\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DuplicateAmbienteException extends OrganizacionDomainException
{
    /**
     * @param  array{id: int, nombre: string, sucursal: string}|null  $conflict
     */
    public function __construct(private readonly ?array $conflict = null)
    {
        parent::__construct('Ya existe un ambiente con ese nombre en la sucursal.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'AMBIENTE_DUPLICADO';
    }

    public function render(Request $request): JsonResponse
    {
        $payload = [
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['nombre' => [$this->getMessage()]],
        ];

        if ($this->conflict !== null) {
            $payload['conflict'] = $this->conflict;
        }

        return response()->json($payload, $this->status());
    }
}
