<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfesionalNoDisponibleException extends InscripcionesAtencionesDomainException
{
    public function __construct(
        public readonly int $profesionalId,
        public readonly int $sucursalId,
        string $message = 'El profesional no está activo o no trabaja en la sucursal indicada.',
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_NO_DISPONIBLE';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['profesional_id' => [$this->getMessage()]],
        ], $this->status());
    }
}
