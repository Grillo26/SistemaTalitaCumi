<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfesionalFueraDeSucursalException extends InscripcionesAtencionesDomainException
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
        return 'PROFESIONAL_FUERA_DE_SUCURSAL';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['profesionales' => [$this->getMessage()]],
        ], $this->status());
    }
}
