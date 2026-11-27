<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HoraInvalidaException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message = 'La hora debe tener un formato válido HH:MM.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'HORA_INVALIDA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['hora' => [$this->getMessage()]],
        ], $this->status());
    }
}
