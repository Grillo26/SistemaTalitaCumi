<?php

namespace App\Modules\Organizacion\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StatusReasonRequiredException extends OrganizacionDomainException
{
    public function __construct()
    {
        parent::__construct('El motivo es obligatorio para desactivar el registro.');
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
