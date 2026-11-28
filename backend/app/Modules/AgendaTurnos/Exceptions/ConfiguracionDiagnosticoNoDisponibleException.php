<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RNF-011 — falta configuración de agenda o duración.
 *
 * No se inventan valores por defecto: la operación se bloquea y la
 * configuración debe corregirse.
 */
final class ConfiguracionDiagnosticoNoDisponibleException extends AgendaTurnosDomainException
{
    public function __construct(public readonly string $clave)
    {
        parent::__construct("Falta una configuración válida para coordinar diagnósticos: {$clave}.");
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'CONFIGURACION_DIAGNOSTICO_NO_DISPONIBLE';
    }

    public function context(): array
    {
        return ['clave' => $this->clave];
    }
}
