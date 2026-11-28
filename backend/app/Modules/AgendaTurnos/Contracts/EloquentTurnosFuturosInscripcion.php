<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosInscripcionResumen;

/**
 * Implementación real del pre-chequeo de turnos futuros de una inscripción
 * a sala (conexión 7.2/7.3 con Inscripciones/Estados).
 */
final class EloquentTurnosFuturosInscripcion implements TurnosFuturosInscripcionContract
{
    public function __construct(private readonly AgendaClock $clock) {}

    public function disponible(): bool
    {
        return true;
    }

    public function resumen(int $inscripcionId): TurnosFuturosInscripcionResumen
    {
        $fechas = Turno::query()
            ->where('inscripcion_sala_id', $inscripcionId)
            ->where('estado', 'programado')
            ->futuros($this->clock->hoy(), $this->clock->horaActual())
            ->orderBy('fecha')
            ->get(['fecha'])
            ->map(static fn (Turno $turno): string => $turno->fecha->toDateString())
            ->unique()
            ->values()
            ->all();

        return new TurnosFuturosInscripcionResumen(
            cantidad: count($fechas),
            fechas: $fechas,
        );
    }
}
