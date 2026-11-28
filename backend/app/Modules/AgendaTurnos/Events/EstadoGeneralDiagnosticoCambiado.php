<?php

namespace App\Modules\AgendaTurnos\Events;

use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;

/**
 * 07.05/RF-005 / 07.05/RF-009 / 07.05/RN-004.
 */
final class EstadoGeneralDiagnosticoCambiado
{
    /**
     * @param  list<array{tipo: string, especialidad_id: int, mensaje: string}>  $faltantes
     */
    public function __construct(
        public readonly int $diagnosticoId,
        public readonly int $sucursalId,
        public readonly EstadoGeneralDiagnostico $estadoAnterior,
        public readonly EstadoGeneralDiagnostico $estadoNuevo,
        public readonly array $faltantes,
        public readonly ?int $actorId,
        public readonly ?string $ip,
        public readonly ?string $motivo,
    ) {}
}
