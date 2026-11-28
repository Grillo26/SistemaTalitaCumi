<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Support\Hallazgo;
use App\Modules\AgendaTurnos\Support\TurnoCandidato;
use App\Modules\AgendaTurnos\Support\TurnoExistente;

/**
 * [Reprogramación y Cancelación] RF-004 / RF-011 / RN-005 / CA-004 / CA-007.
 *
 * Reutiliza la regla de solapamiento de 7.2. Paciente y profesional bloquean
 * contra otra sesión; la sala advierte; la misma sesión grupal no cuenta; un
 * feriado es informativo. La regla recibe los turnos ya consultados.
 */
final class DisponibilidadReprogramacionRule
{
    public function __construct(private readonly TurnoSolapamientoRule $solapamiento) {}

    /**
     * @param  list<TurnoExistente>  $existentes
     * @param  array<string, string>  $feriados
     * @return list<Hallazgo>
     */
    public function evaluar(
        TurnoCandidato $candidato,
        array $existentes,
        int $turnoOriginalId,
        array $feriados = [],
    ): array {
        $hallazgos = $this->solapamiento->evaluar(
            $candidato,
            $existentes,
            [$turnoOriginalId],
        );

        if (array_key_exists($candidato->fecha, $feriados)) {
            $descripcion = trim($feriados[$candidato->fecha]);
            $hallazgos[] = new Hallazgo(
                tipo: 'feriado',
                severidad: SeveridadHallazgo::INFORMATIVO,
                mensaje: $descripcion === ''
                    ? 'La nueva fecha es feriado.'
                    : "La nueva fecha es feriado: {$descripcion}.",
                fecha: $candidato->fecha,
                horaInicio: $candidato->horaInicio,
            );
        }

        return $hallazgos;
    }
}
