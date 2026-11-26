<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\MotivoNoRenovable;
use App\Modules\InscripcionesAtenciones\Exceptions\RenovacionNoPermitidaException;

/**
 * [Vigencia y Renovación] CU-02 FE-01 / FE-02 / RN-004.
 *
 * Regla pura sobre datos ya resueltos por los contratos.
 */
final class RenovacionElegibleRule
{
    public static function motivo(
        bool $pacienteActivo,
        bool $servicioActivo,
        ?bool $salaActiva,
        bool $retirada,
        bool $renovada,
    ): ?MotivoNoRenovable {
        if ($retirada) {
            return MotivoNoRenovable::ENTIDAD_RETIRADA;
        }

        if ($renovada) {
            return MotivoNoRenovable::ENTIDAD_RENOVADA;
        }

        if (! $pacienteActivo) {
            return MotivoNoRenovable::PACIENTE_INACTIVO;
        }

        if (! $servicioActivo) {
            return MotivoNoRenovable::SERVICIO_INACTIVO;
        }

        if ($salaActiva === false) {
            return MotivoNoRenovable::SALA_INACTIVA;
        }

        return null;
    }

    public static function assertElegible(
        bool $pacienteActivo,
        bool $servicioActivo,
        ?bool $salaActiva,
        bool $retirada,
        bool $renovada,
    ): void {
        $motivo = self::motivo(
            pacienteActivo: $pacienteActivo,
            servicioActivo: $servicioActivo,
            salaActiva: $salaActiva,
            retirada: $retirada,
            renovada: $renovada,
        );

        if ($motivo !== null) {
            throw new RenovacionNoPermitidaException($motivo);
        }
    }
}
