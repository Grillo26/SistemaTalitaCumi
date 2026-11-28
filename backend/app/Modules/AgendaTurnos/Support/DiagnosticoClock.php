<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Exceptions\ConfiguracionDiagnosticoNoDisponibleException;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * 07.05/RNF-011 — reloj del submódulo en la zona configurada.
 *
 * La fecha/hora de los turnos se almacena en la zona local de la agenda,
 * igual que 07.1–07.4. No se hardcodea America/La_Paz.
 */
final class DiagnosticoClock
{
    public const ZONA_HORARIA = 'zona_horaria';

    private ?string $zona = null;

    public function __construct(private readonly ParameterReaderContract $parametros) {}

    public function zonaHoraria(): string
    {
        if ($this->zona !== null) {
            return $this->zona;
        }

        try {
            $valor = trim((string) $this->parametros->obtener(self::ZONA_HORARIA)->valor);
        } catch (Throwable) {
            throw new ConfiguracionDiagnosticoNoDisponibleException(self::ZONA_HORARIA);
        }

        if ($valor === '' || ! in_array($valor, timezone_identifiers_list(), true)) {
            throw new ConfiguracionDiagnosticoNoDisponibleException(self::ZONA_HORARIA);
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

    public function fechaHora(string $fecha, string $hora): CarbonImmutable
    {
        return CarbonImmutable::parse($fecha.' '.$hora, $this->zonaHoraria());
    }
}
