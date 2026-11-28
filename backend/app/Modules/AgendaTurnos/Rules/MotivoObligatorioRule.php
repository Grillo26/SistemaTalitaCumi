<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Support\MotivoTurnoData;

/**
 * [Reprogramación y Cancelación] RN-001 / RF-003 / RNF-011.
 *
 * El motivo debe existir, estar activo, aplicar a la acción y, si la
 * configuración lo exige, llevar texto libre. No se hardcodea el código
 * "otro": la exigencia viene del catálogo.
 */
final class MotivoObligatorioRule
{
    /**
     * @return list<string>
     */
    public function errores(
        ?MotivoTurnoData $motivo,
        string $accion,
        ?string $textoLibre,
    ): array {
        if ($motivo === null) {
            return ['Seleccioná un motivo de la lista.'];
        }

        if (! $motivo->activo) {
            return ['El motivo seleccionado está inactivo.'];
        }

        $aplica = $accion === 'reprogramar'
            ? $motivo->aplicaReprogramar
            : $motivo->aplicaCancelar;

        if (! $aplica) {
            return ['El motivo seleccionado no aplica a esta acción.'];
        }

        if ($motivo->requiereTexto && trim((string) $textoLibre) === '') {
            return ['El motivo seleccionado exige un texto explicativo.'];
        }

        return [];
    }
}
