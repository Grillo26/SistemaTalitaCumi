<?php

namespace App\Modules\Organizacion\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TipoSalaInUseException extends OrganizacionDomainException
{
    public function __construct(
        private readonly string $tipoSalaName,
        private readonly int $activeSalasCount,
    ) {
        parent::__construct(sprintf(
            'No se puede desactivar el tipo de sala "%s" porque tiene %d sala(s) activa(s).',
            $tipoSalaName,
            $activeSalasCount,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TIPO_SALA_IN_USE';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'active_salas_count' => $this->activeSalasCount,
        ], $this->status());
    }
}
