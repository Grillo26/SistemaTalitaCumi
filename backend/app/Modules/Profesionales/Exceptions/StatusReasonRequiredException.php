<?php

namespace App\Modules\Profesionales\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StatusReasonRequiredException extends ProfesionalesDomainException
{
    public function __construct(private readonly string $entityLabel)
    {
        parent::__construct("El motivo es obligatorio para cambiar el estado de {$entityLabel}.");
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'STATUS_REASON_REQUIRED';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['motivo' => [$this->getMessage()]],
        ], $this->status());
    }
}
