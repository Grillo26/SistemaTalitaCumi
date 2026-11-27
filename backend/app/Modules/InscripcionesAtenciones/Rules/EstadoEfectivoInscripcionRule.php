<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\EstadoVigencia;

/**
 * [Estados Paciente e Inscripción] RF-002 / RF-004 / RN-003 / CA-001.
 *
 * Estado efectivo de una inscripción. La regla es pura: recibe el estado
 * almacenado, la fecha de fin, "hoy" y si existe una sucesora activa.
 *
 * Precedencia: Retirada > EnPausa > Renovada > Vencida > Activa.
 * Reutiliza el clasificador de Vigencia con ventana 0 para no duplicar el
 * parseo y la comparación de fechas.
 */
final class EstadoEfectivoInscripcionRule
{
    public static function effective(
        EstadoInscripcion $almacenado,
        string $fechaFin,
        string $hoy,
        bool $renovada,
    ): EstadoInscripcion {
        if ($almacenado === EstadoInscripcion::RETIRADA) {
            return EstadoInscripcion::RETIRADA;
        }

        if ($almacenado === EstadoInscripcion::EN_PAUSA) {
            return EstadoInscripcion::EN_PAUSA;
        }

        if ($almacenado === EstadoInscripcion::RENOVADA) {
            return EstadoInscripcion::RENOVADA;
        }

        if ($renovada) {
            return EstadoInscripcion::RENOVADA;
        }

        if ($almacenado === EstadoInscripcion::VENCIDA) {
            return EstadoInscripcion::VENCIDA;
        }

        $base = VigenciaClassifierRule::classify(
            fechaFin: $fechaFin,
            hoy: $hoy,
            ventanaDias: 0,
            retirada: false,
            renovada: false,
        );

        return $base === EstadoVigencia::VENCIDO
            ? EstadoInscripcion::VENCIDA
            : EstadoInscripcion::ACTIVA;
    }

    public static function diasHastaVencimiento(string $fechaFin, string $hoy): int
    {
        return VigenciaClassifierRule::diasHastaVencimiento($fechaFin, $hoy);
    }
}
