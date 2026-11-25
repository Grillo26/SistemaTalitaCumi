<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Pacientes\Exceptions\PacienteConfigurationUnavailableException;
use App\Modules\Pacientes\Exceptions\PacienteInvalidBirthDateException;
use App\Modules\Pacientes\Models\Paciente;
use Carbon\CarbonImmutable;

/**
 * Cálculo de edad en lectura (04.01/RF-002, 04.01/CA-005).
 *
 * Usa `zona_horaria` del módulo 01 para definir “hoy”. No almacena la edad.
 */
final class PacienteAgeCalculator
{
    public function __construct(private readonly ParameterReaderContract $parameters) {}

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone())->startOfDay();
    }

    /**
     * @return array{anios: int, meses: int, dias: int, total_meses: int, etiqueta: string}
     */
    public function for(Paciente $paciente): array
    {
        $tz = $this->timezone();
        $today = CarbonImmutable::now($tz)->startOfDay();
        $birth = CarbonImmutable::parse($paciente->fecha_nacimiento->toDateString(), $tz)->startOfDay();

        if ($birth->isFuture()) {
            throw new PacienteInvalidBirthDateException;
        }

        $diff = $birth->diff($today);
        $anios = (int) $diff->y;
        $meses = (int) $diff->m;
        $dias = (int) $diff->d;
        $totalMeses = ($anios * 12) + $meses;

        return [
            'anios' => $anios,
            'meses' => $meses,
            'dias' => $dias,
            'total_meses' => $totalMeses,
            'etiqueta' => $this->label($anios, $meses, $dias, $totalMeses),
        ];
    }

    private function label(int $anios, int $meses, int $dias, int $totalMeses): string
    {
        if ($totalMeses < 1) {
            return $dias.' '.($dias === 1 ? 'día' : 'días');
        }

        if ($anios < 1) {
            return $totalMeses.' '.($totalMeses === 1 ? 'mes' : 'meses');
        }

        $yearLabel = $anios.' '.($anios === 1 ? 'año' : 'años');

        if ($anios < 3) {
            return $meses === 0
                ? $yearLabel
                : $yearLabel.' y '.$meses.' '.($meses === 1 ? 'mes' : 'meses');
        }

        return $yearLabel;
    }

    private function timezone(): string
    {
        try {
            $value = $this->parameters->obtener('zona_horaria')->valor;
        } catch (ParameterNotFoundException) {
            throw new PacienteConfigurationUnavailableException('zona_horaria');
        }

        if (! is_string($value) || $value === '') {
            throw new PacienteConfigurationUnavailableException('zona_horaria');
        }

        return $value;
    }
}
