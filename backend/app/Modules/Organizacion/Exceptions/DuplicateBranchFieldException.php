<?php

namespace App\Modules\Organizacion\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DuplicateBranchFieldException extends OrganizacionDomainException
{
    public function __construct(private readonly string $field)
    {
        parent::__construct(sprintf(
            'Ya existe una sucursal con el mismo %s.',
            $field === 'codigo' ? 'código' : 'nombre',
        ));
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'BRANCH_DUPLICATE_FIELD';
    }

    public function field(): string
    {
        return $this->field;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                $this->field => [$this->getMessage()],
            ],
        ], $this->status());
    }
}
