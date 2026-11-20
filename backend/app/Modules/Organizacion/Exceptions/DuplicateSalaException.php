<?php

namespace App\Modules\Organizacion\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DuplicateSalaException extends OrganizacionDomainException
{
    /**
     * @param  array{id: int, nombre: string, tipo_sala: string, turno: string, sucursal: string}|null  $conflict
     */
    public function __construct(private readonly ?array $conflict = null)
    {
        $message = 'Ya existe una sala para esa combinación de tipo, turno y sucursal.';

        if ($conflict !== null) {
            $message .= sprintf(' La sala en conflicto es "%s".', $conflict['nombre']);
        }

        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'SALA_DUPLICADA';
    }

    public function render(Request $request): JsonResponse
    {
        $payload = [
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                'tipo_sala_id' => [$this->getMessage()],
            ],
        ];

        if ($this->conflict !== null) {
            $payload['conflict'] = $this->conflict;
        }

        return response()->json($payload, $this->status());
    }
}
