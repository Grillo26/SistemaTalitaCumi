<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\AccionEstado;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Exceptions\TransicionEstadoNoPermitidaException;

/**
 * [Estados Paciente e Inscripción] RF-004 / RN-003 / RNF-010.
 *
 * Única matriz de transiciones de inscripción del submódulo. Las
 * transiciones manuales son las que un usuario puede ejecutar; las del
 * sistema corresponden a Vencida y Renovada, que se derivan y no tienen
 * endpoint de cambio.
 */
final class EstadoInscripcionTransitionRule
{
    public const MANUAL = 'manual';

    public const SISTEMA = 'sistema';

    public const INVALIDA = null;

    /**
     * @return array{0: EstadoInscripcion, 1: string}|null
     */
    public static function tipo(EstadoInscripcion $desde, EstadoInscripcion $hacia): ?array
    {
        $key = $desde->value.'>'.$hacia->value;

        return match ($key) {
            'activa>en_pausa' => [$hacia, self::MANUAL],
            'activa>vencida' => [$hacia, self::SISTEMA],
            'activa>renovada' => [$hacia, self::SISTEMA],
            'activa>retirada' => [$hacia, self::MANUAL],
            'en_pausa>activa' => [$hacia, self::MANUAL],
            'en_pausa>retirada' => [$hacia, self::MANUAL],
            'vencida>renovada' => [$hacia, self::SISTEMA],
            default => null,
        };
    }

    public static function esPermitida(EstadoInscripcion $desde, EstadoInscripcion $hacia): bool
    {
        return self::tipo($desde, $hacia) !== null;
    }

    public static function esManual(EstadoInscripcion $desde, EstadoInscripcion $hacia): bool
    {
        $tipo = self::tipo($desde, $hacia);

        return $tipo !== null && $tipo[1] === self::MANUAL;
    }

    public static function assertPermitida(EstadoInscripcion $desde, EstadoInscripcion $hacia): void
    {
        if (! self::esPermitida($desde, $hacia)) {
            throw new TransicionEstadoNoPermitidaException($desde, $hacia);
        }
    }

    public static function assertManual(EstadoInscripcion $desde, AccionEstado $accion): void
    {
        $hacia = $accion->estadoDestino();
        $tipo = self::tipo($desde, $hacia);

        if ($tipo === null || $tipo[1] !== self::MANUAL) {
            throw TransicionEstadoNoPermitidaException::paraAccion($desde, $accion);
        }
    }

    public static function assertPuedePausar(EstadoInscripcion $desde): void
    {
        self::assertManual($desde, AccionEstado::PAUSAR);
    }

    public static function assertPuedeReactivar(EstadoInscripcion $desde): void
    {
        self::assertManual($desde, AccionEstado::REACTIVAR);
    }

    public static function assertPuedeRetirar(EstadoInscripcion $desde): void
    {
        self::assertManual($desde, AccionEstado::RETIRAR);
    }

    /**
     * @return list<AccionEstado>
     */
    public static function accionesManuales(EstadoInscripcion $desde): array
    {
        return array_values(array_filter(
            AccionEstado::cases(),
            static fn (AccionEstado $accion): bool => self::esManual($desde, $accion->estadoDestino()),
        ));
    }
}
