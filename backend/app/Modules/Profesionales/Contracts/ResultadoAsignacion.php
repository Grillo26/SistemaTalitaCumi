<?php

namespace App\Modules\Profesionales\Contracts;

/**
 * Resultado tipado de la validación de asignación (03/RN-07, 03/RN-08, 03/RN-09).
 *
 * Devuelve el motivo del rechazo para que agenda y diagnósticos puedan mostrar
 * un mensaje claro sin acoplarse a los modelos del módulo 03.
 */
final readonly class ResultadoAsignacion
{
    public function __construct(
        public bool $puede,
        public string $codigo,
        public string $mensaje,
    ) {}

    public static function valida(): self
    {
        return new self(
            puede: true,
            codigo: 'ASIGNACION_VALIDA',
            mensaje: 'El profesional puede asignarse a la especialidad y sucursal indicadas.',
        );
    }

    public static function rechazo(string $codigo, string $mensaje): self
    {
        return new self(puede: false, codigo: $codigo, mensaje: $mensaje);
    }
}
