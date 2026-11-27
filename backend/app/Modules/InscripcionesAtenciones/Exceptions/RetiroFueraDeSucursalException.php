<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Estados Paciente e Inscripción] RN-012 / punto 5 de la especificación.
 */
final class RetiroFueraDeSucursalException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  list<int>  $sucursalesFaltantes
     */
    public function __construct(public readonly array $sucursalesFaltantes)
    {
        parent::__construct(
            'No podés retirar al paciente porque tiene inscripciones activas o en pausa '
            .'en sucursales que no tenés asignadas: '.implode(', ', $sucursalesFaltantes).'.',
        );
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'RETIRO_FUERA_DE_SUCURSAL';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'sucursales_faltantes' => $this->sucursalesFaltantes,
        ], $this->status());
    }
}
