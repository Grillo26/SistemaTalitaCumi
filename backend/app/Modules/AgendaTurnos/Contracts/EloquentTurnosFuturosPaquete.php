<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosPaqueteResumen;

/**
 * Implementación real del pre-chequeo de turnos futuros de un paquete.
 */
final class EloquentTurnosFuturosPaquete implements TurnosFuturosPaqueteContract
{
    public function __construct(private readonly AgendaClock $clock) {}

    public function disponible(): bool
    {
        return true;
    }

    public function resumen(int $paqueteId): TurnosFuturosPaqueteResumen
    {
        $fechas = Turno::query()
            ->where('paquete_sesiones_id', $paqueteId)
            ->where('estado', 'programado')
            ->futuros($this->clock->hoy(), $this->clock->horaActual())
            ->orderBy('fecha')
            ->get(['fecha'])
            ->map(static fn (Turno $turno): string => $turno->fecha->toDateString())
            ->unique()
            ->values()
            ->all();

        return new TurnosFuturosPaqueteResumen(
            cantidad: count($fechas),
            fechas: $fechas,
        );
    }
}
