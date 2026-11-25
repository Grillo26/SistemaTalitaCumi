<?php

namespace App\Modules\Pacientes\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PacienteDiagnosticCatalogUnavailableException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El catálogo de tipos de diagnóstico todavía no está disponible (módulo 05). Dejá el campo vacío o completalo cuando exista el catálogo.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'DIAGNOSTIC_CATALOG_UNAVAILABLE';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['tipo_diagnostico_id' => [$this->getMessage()]],
        ], $this->status());
    }
}
