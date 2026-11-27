<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Turnos futuros de una inscripción para el pre-chequeo de baja (CU-03/FE-01).
 */
final readonly class TurnosFuturosInscripcionResumen
{
    /**
     * @param  list<string>  $fechas
     */
    public function __construct(
        public int $cantidad,
        public array $fechas = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cantidad' => $this->cantidad,
            'fechas' => $this->fechas,
        ];
    }
}
