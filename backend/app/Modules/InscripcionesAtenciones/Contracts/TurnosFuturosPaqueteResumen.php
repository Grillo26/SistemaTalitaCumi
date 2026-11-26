<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Turnos futuros de un paquete para el pre-chequeo de baja/modificación.
 */
final readonly class TurnosFuturosPaqueteResumen
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
