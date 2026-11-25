<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Servicios vigentes del paciente (módulo 06).
 *
 * Implementación futura en 06. Debe filtrar por las sucursales accesibles y
 * por el contexto de sucursal si se informa.
 */
interface PatientServicesSummaryContract
{
    public function disponible(): bool;

    /**
     * @param  list<int>|null  $sucursalesAccesibles  NULL = alcance global
     * @return list<ServicioVigenteResumen>
     */
    public function serviciosVigentes(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
        ?int $sucursalContexto = null,
    ): array;

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     * @return list<int>
     */
    public function sucursalesConServicios(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
    ): array;
}
