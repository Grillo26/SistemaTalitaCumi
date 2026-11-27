<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Datos de una inscripción a sala o paquete para la programación de turnos
 * del módulo 07.
 *
 * Para las inscripciones a sala, `diasSemana`, `horaInicio` y
 * `duracionMinutos` son null: el patrón de la sala lo expone Organización a
 * través de `SalaPatronContract`.
 */
final readonly class OrigenTurnosResumen
{
    /**
     * @param  list<int>|null  $diasSemana
     */
    public function __construct(
        public string $tipo,
        public int $id,
        public int $sucursalId,
        public int $pacienteId,
        public int $servicioId,
        public ?int $salaId,
        public ?int $profesionalId,
        public string $fechaInicio,
        public string $fechaFin,
        public ?int $cantidadSesiones,
        public string $estadoEfectivo,
        public ?array $diasSemana = null,
        public ?string $horaInicio = null,
        public ?int $duracionMinutos = null,
    ) {}
}
