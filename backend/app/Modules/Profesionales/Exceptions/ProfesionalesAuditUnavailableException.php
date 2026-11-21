<?php

namespace App\Modules\Profesionales\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class ProfesionalesAuditUnavailableException extends ProfesionalesDomainException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct('No se pudo registrar la operación en la bitácora. Intentá nuevamente.', 0, $previous);
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'AUDIT_UNAVAILABLE';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
        ], $this->status());
    }
}
