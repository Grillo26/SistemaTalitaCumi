<?php

namespace App\Modules\Profesionales\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EspecialidadEnUsoException extends ProfesionalesDomainException
{
    public function __construct(
        private readonly string $nombre,
        private readonly int $profesionalesActivos,
    ) {
        parent::__construct(sprintf(
            'No se puede desactivar la especialidad "%s" porque tiene %d profesional(es) activo(s) asociado(s).',
            $nombre,
            $profesionalesActivos,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ESPECIALIDAD_IN_USE';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'profesionales_activos_count' => $this->profesionalesActivos,
        ], $this->status());
    }
}
