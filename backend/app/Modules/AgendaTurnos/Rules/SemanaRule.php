<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Exceptions\ConfiguracionAgendaIncompletaException;
use App\Modules\AgendaTurnos\Exceptions\SemanaInvalidaException;
use App\Modules\AgendaTurnos\Support\DiaAgenda;
use App\Modules\AgendaTurnos\Support\SemanaAgenda;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Throwable;

/**
 * [Agenda Semanal] RF-001, RF-002, RN-005.
 *
 * Normaliza cualquier fecha al lunes de su semana en la zona de la agenda.
 * El día actual se inyecta; la regla no llama a now().
 */
final class SemanaRule
{
    /**
     * @param  list<int>  $diasHabilitados  Días ISO (1=lunes, 7=domingo)
     */
    public function __invoke(
        ?string $fecha,
        CarbonImmutable $hoy,
        array $diasHabilitados,
        string $zona = 'America/La_Paz',
    ): SemanaAgenda {
        $hoyLocal = $hoy->setTimezone($zona)->startOfDay();

        try {
            $referencia = $fecha === null || trim($fecha) === ''
                ? $hoyLocal
                : CarbonImmutable::parse($fecha, $zona)->setTimezone($zona);
        } catch (Throwable) {
            throw new SemanaInvalidaException($fecha);
        }

        $dias = $this->normalizarDias($diasHabilitados);
        $inicio = $referencia->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $fin = $inicio->addDays(6)->startOfDay();

        $diasAgenda = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $dia = $inicio->addDays($offset);
            $iso = (int) $dia->isoWeekday();

            $diasAgenda[] = new DiaAgenda(
                fecha: $dia->toDateString(),
                diaSemana: $iso,
                visible: in_array($iso, $dias, true),
                esHoy: $dia->isSameDay($hoyLocal),
            );
        }

        return new SemanaAgenda(
            inicio: $inicio->toDateString(),
            fin: $fin->toDateString(),
            dias: $diasAgenda,
        );
    }

    /**
     * @param  list<int>  $diasHabilitados
     * @return list<int>
     */
    private function normalizarDias(array $diasHabilitados): array
    {
        if ($diasHabilitados === []) {
            throw new ConfiguracionAgendaIncompletaException(
                'No hay días habilitados para mostrar la agenda.',
            );
        }

        $dias = [];
        $vistos = [];

        foreach ($diasHabilitados as $dia) {
            $dia = (int) $dia;

            if ($dia < 1 || $dia > 7 || isset($vistos[$dia])) {
                throw new ConfiguracionAgendaIncompletaException(
                    'Los días habilitados de la agenda deben ser números únicos entre 1 y 7.',
                );
            }

            $vistos[$dia] = true;
            $dias[] = $dia;
        }

        sort($dias);

        return $dias;
    }
}
