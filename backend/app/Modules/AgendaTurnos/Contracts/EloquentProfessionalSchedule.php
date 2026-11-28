<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Profesionales\Contracts\ProfessionalScheduleContract;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Implementación real de `ProfessionalScheduleContract` (03/RT-09).
 *
 * El rango llega en UTC; los turnos se guardan en la zona local de la agenda.
 * Se consideran vigentes los turnos programados y asistidos de todas las
 * sucursales. El mensaje de rechazo del consumidor es genérico: este contrato
 * solo devuelve un booleano y no expone datos de otras sucursales.
 */
final class EloquentProfessionalSchedule implements ProfessionalScheduleContract
{
    public function __construct(private readonly ParameterReaderContract $parametros) {}

    public function haySolapamiento(
        int $profesionalId,
        CarbonImmutable $inicio,
        CarbonImmutable $fin,
    ): bool {
        $zona = $this->zonaHoraria();
        $inicioLocal = $inicio->setTimezone($zona);
        $finLocal = $fin->setTimezone($zona);

        if ($finLocal <= $inicioLocal) {
            return false;
        }

        $turnos = Turno::query()
            ->where('profesional_id', $profesionalId)
            ->vigentes()
            ->whereBetween('fecha', [$inicioLocal->toDateString(), $finLocal->toDateString()])
            ->get();

        foreach ($turnos as $turno) {
            $inicioTurno = $this->inicio($turno, $zona);
            $finTurno = $this->fin($turno, $zona);

            if ($inicioLocal < $finTurno && $inicioTurno < $finLocal) {
                return true;
            }
        }

        return false;
    }

    public function tieneAsignacionesFuturas(int $profesionalId, CarbonImmutable $desde): bool
    {
        $zona = $this->zonaHoraria();
        $desdeLocal = $desde->setTimezone($zona);

        $turnos = Turno::query()
            ->where('profesional_id', $profesionalId)
            ->where('estado', 'programado')
            ->where('fecha', '>=', $desdeLocal->toDateString())
            ->get();

        foreach ($turnos as $turno) {
            if ($this->fin($turno, $zona) > $desdeLocal) {
                return true;
            }
        }

        return false;
    }

    private function zonaHoraria(): string
    {
        try {
            $valor = trim((string) $this->parametros->obtener('zona_horaria')->valor);
        } catch (Throwable) {
            return (string) config('app.timezone', 'America/La_Paz');
        }

        return in_array($valor, timezone_identifiers_list(), true)
            ? $valor
            : (string) config('app.timezone', 'America/La_Paz');
    }

    private function inicio(Turno $turno, string $zona): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $turno->fecha->toDateString().' '.$turno->hora_inicio,
            $zona,
        );
    }

    private function fin(Turno $turno, string $zona): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $turno->fecha->toDateString().' '.$turno->hora_fin,
            $zona,
        );
    }
}
