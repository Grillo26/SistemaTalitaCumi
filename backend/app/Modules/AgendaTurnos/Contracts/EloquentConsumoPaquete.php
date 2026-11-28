<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteResumen;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosContract;

/**
 * 07.04/RF-002 / 06.02 — conteos de turnos de un paquete.
 *
 * El saldo lo calcula 06.02 con `SaldoSesionesRule`. Este contrato solo
 * devuelve los conteos; no escribe contadores.
 */
final class EloquentConsumoPaquete implements ConsumoPaqueteContract
{
    public function __construct(private readonly OrigenTurnosContract $origenes) {}

    public function disponible(): bool
    {
        return true;
    }

    public function resumen(int $paqueteId): ConsumoPaqueteResumen
    {
        $origen = $this->origenes->obtener('paquete_sesiones', $paqueteId);

        $query = Turno::query()
            ->where('paquete_sesiones_id', $paqueteId)
            ->where('estado', '!=', EstadoTurno::REPROGRAMADO->value);

        return new ConsumoPaqueteResumen(
            pactadas: (int) ($origen?->cantidadSesiones ?? 0),
            realizadas: (int) (clone $query)->where('estado', EstadoTurno::ASISTIO->value)->count(),
            faltadas: (int) (clone $query)->where('estado', EstadoTurno::FALTO->value)->count(),
            canceladas: (int) (clone $query)->where('estado', EstadoTurno::CANCELADO->value)->count(),
        );
    }
}
