<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.04/RNF-011 — falta un parámetro de configuración de asistencia.
 */
final class ConfiguracionAsistenciaNoDisponibleException extends AgendaTurnosDomainException
{
    public function __construct(public readonly string $clave)
    {
        parent::__construct(
            "No se pudo leer la configuración de asistencia «{$clave}».",
        );
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'CONFIGURACION_ASISTENCIA_NO_DISPONIBLE';
    }

    public function context(): array
    {
        return ['clave' => $this->clave];
    }
}
