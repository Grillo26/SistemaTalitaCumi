<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Exceptions\ConfiguracionAsistenciaNoDisponibleException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * 07.04/RNF-011 — reloj y ventana de edición configurables.
 *
 * Lee `zona_horaria` y `ventana_edicion_asistencia_horas` del catálogo de
 * Organización. No inventa valores: si faltan, falla de forma controlada.
 */
final class AsistenciaClock
{
    public const ZONA_HORARIA = 'zona_horaria';

    public const VENTANA_HORAS = 'ventana_edicion_asistencia_horas';

    private ?string $zona = null;

    private ?int $ventana = null;

    public function __construct(private readonly ParameterReaderContract $parametros) {}

    public function zonaHoraria(): string
    {
        if ($this->zona !== null) {
            return $this->zona;
        }

        try {
            $valor = trim((string) $this->parametros->obtener(self::ZONA_HORARIA)->valor);
        } catch (Throwable) {
            throw new ConfiguracionAsistenciaNoDisponibleException(self::ZONA_HORARIA);
        }

        if ($valor === '' || ! in_array($valor, timezone_identifiers_list(), true)) {
            throw new ConfiguracionAsistenciaNoDisponibleException(self::ZONA_HORARIA);
        }

        return $this->zona = $valor;
    }

    public function ahora(): CarbonImmutable
    {
        return CarbonImmutable::now($this->zonaHoraria());
    }

    public function hoy(): string
    {
        return $this->ahora()->toDateString();
    }

    public function horaActual(): string
    {
        return $this->ahora()->format('H:i');
    }

    public function ventanaHoras(): int
    {
        if ($this->ventana !== null) {
            return $this->ventana;
        }

        try {
            $valor = $this->parametros->obtener(self::VENTANA_HORAS)->valor;
        } catch (Throwable) {
            throw new ConfiguracionAsistenciaNoDisponibleException(self::VENTANA_HORAS);
        }

        if (! is_numeric($valor)) {
            throw new ConfiguracionAsistenciaNoDisponibleException(self::VENTANA_HORAS);
        }

        return $this->ventana = max(0, (int) $valor);
    }

    public function registradaEn(Turno $turno): ?CarbonImmutable
    {
        if ($turno->asistencia_registrada_en === null) {
            return null;
        }

        return CarbonImmutable::parse($turno->asistencia_registrada_en)
            ->setTimezone($this->zonaHoraria());
    }

    public function inicioTurno(Turno $turno): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $turno->fecha->toDateString().' '.$turno->hora_inicio,
            $this->zonaHoraria(),
        );
    }
}
