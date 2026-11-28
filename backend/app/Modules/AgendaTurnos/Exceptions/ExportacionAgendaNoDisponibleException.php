<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

/**
 * Stub de exportación mientras no exista infraestructura PDF.
 *
 * Extiende LogicException para cumplir el contrato de stubs y expone 503 para
 * que la UI muestre un error claro sin descargar un archivo falso.
 */
final class ExportacionAgendaNoDisponibleException extends LogicException
{
    public function __construct()
    {
        parent::__construct(
            'La exportación de la agenda no está disponible: falta instalar la infraestructura PDF.',
        );
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'EXPORTACION_NO_DISPONIBLE',
            'modulo' => 'Agenda y turnos',
        ], 503);
    }
}
