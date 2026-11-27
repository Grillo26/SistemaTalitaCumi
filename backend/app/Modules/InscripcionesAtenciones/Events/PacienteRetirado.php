<?php

namespace App\Modules\InscripcionesAtenciones\Events;

use App\Modules\Pacientes\Enums\EstadoPaciente;

/**
 * [Estados Paciente e Inscripción] RF-005 / CU-02 / CA-006.
 *
 * Se emite después de retirar al paciente y sus inscripciones arrastradas.
 * El cambio de estado del paciente ya se audita con PacienteStatusChanged;
 * este evento agrega el detalle de la cascada para otros módulos.
 */
final readonly class PacienteRetirado
{
    /**
     * @param  list<int>  $sucursalIds
     * @param  list<array{tipo: string, id: int, sucursal_id: int}>  $inscripciones
     */
    public function __construct(
        public int $pacienteId,
        public array $sucursalIds,
        public array $inscripciones,
        public string $motivoCodigo,
        public ?string $motivoTexto,
        public string $fecha,
        public int $actorId,
        public ?string $ip,
        public EstadoPaciente $from,
        public EstadoPaciente $to,
    ) {}
}
