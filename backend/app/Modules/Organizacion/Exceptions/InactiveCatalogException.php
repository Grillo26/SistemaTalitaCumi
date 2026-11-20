<?php

namespace App\Modules\Organizacion\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InactiveCatalogException extends OrganizacionDomainException
{
    public function __construct(
        private readonly string $catalogLabel,
        private readonly string $field,
        string $name,
    ) {
        parent::__construct(sprintf('El/la %s "%s" está inactivo/a y no puede usarse.', $this->catalogLabel, $name));
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'CATALOGO_INACTIVO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [$this->field => [$this->getMessage()]],
        ], $this->status());
    }
}
