<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Support\FilaPreview;
use App\Modules\AgendaTurnos\Support\ResumenGeneracion;

/**
 * [Programación de Turnos] RF-014 / RN-007.
 *
 * Calcula las cantidades del resumen y si la confirmación está habilitada.
 * Un bloqueo siempre impide confirmar; una advertencia (de sala o por faltar
 * sesiones del período) exige confirmación explícita del cliente.
 */
final class ResumenGeneracionRule
{
    /**
     * @param  list<FilaPreview>  $filas
     */
    public function resumir(
        array $filas,
        bool $confirmarAdvertencias,
        int $faltantes = 0,
    ): ResumenGeneracion {
        $generables = 0;
        $bloqueados = 0;
        $advertencias = 0;
        $feriados = 0;
        $pasadas = 0;
        $omitidas = 0;

        foreach ($filas as $fila) {
            if ($fila->estado === 'feriado') {
                $feriados++;
            } elseif ($fila->estado === 'pasada') {
                $pasadas++;
            } elseif ($fila->estado === 'omitida') {
                $omitidas++;
            }

            $tieneBloqueo = false;
            $tieneAdvertencia = false;

            foreach ($fila->hallazgos as $hallazgo) {
                if ($hallazgo->severidad === SeveridadHallazgo::BLOQUEO) {
                    $tieneBloqueo = true;
                }

                if ($hallazgo->severidad === SeveridadHallazgo::ADVERTENCIA) {
                    $tieneAdvertencia = true;
                }
            }

            if ($tieneBloqueo) {
                $bloqueados++;
            }

            if ($tieneAdvertencia) {
                $advertencias++;
            }

            if ($fila->generable && $fila->estado === 'generable') {
                $generables++;
            }
        }

        $requiereConfirmar = $advertencias > 0 || $faltantes > 0;
        $puedeConfirmar = $bloqueados === 0
            && $generables > 0
            && (! $requiereConfirmar || $confirmarAdvertencias);

        return new ResumenGeneracion(
            generables: $generables,
            bloqueados: $bloqueados,
            advertencias: $advertencias,
            feriados: $feriados,
            pasadas: $pasadas,
            omitidas: $omitidas,
            faltantes: $faltantes,
            requiereConfirmarAdvertencias: $requiereConfirmar,
            puedeConfirmar: $puedeConfirmar,
        );
    }
}
