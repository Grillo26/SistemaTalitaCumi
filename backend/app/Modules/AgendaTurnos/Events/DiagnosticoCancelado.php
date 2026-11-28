<?php

namespace App\Modules\AgendaTurnos\Events;

use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;

/**
 * 07.05/RF-007 / 07.05/RF-009 / 07.05/CA-007.
 */
final class DiagnosticoCancelado
{
    /**
     * @param  list<int>  $turnosCancelados
     */
    public function __construct(
        public readonly int $diagnosticoId,
        public readonly int $sucursalId,
        public readonly int $pacienteId,
        public readonly EstadoGeneralDiagnostico $estadoAnterior,
        public readonly string $motivo,
        public readonly array $turnosCancelados,
        public readonly int $turnosConservados,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
