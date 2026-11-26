<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DiasSemanaInvalidosException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message = 'Seleccioná al menos un día entre lunes y sábado, sin repetidos.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'DIAS_SEMANA_INVALIDOS';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['dias_semana' => [$this->getMessage()]],
        ], $this->status());
    }
}
