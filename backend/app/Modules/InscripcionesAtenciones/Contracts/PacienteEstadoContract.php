<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * [Estados Paciente e Inscripción] RF-001 / RF-003 / RF-011 / RF-013.
 *
 * Contrato de lectura/escritura del estado del paciente. La persistencia y
 * la máquina de estados base siguen viviendo en el módulo Pacientes.
 */
interface PacienteEstadoContract
{
    public function resumen(int $pacienteId, int $actorId): PacienteEstadoResumen;

    public function cambiarEstado(
        int $pacienteId,
        EstadoPaciente $nuevoEstado,
        string $motivo,
        int $versionEsperada,
        int $actorId,
        ?string $ip = null,
    ): PacienteEstadoResumen;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listarPorEstado(array $filtros, int $actorId): LengthAwarePaginator;
}
