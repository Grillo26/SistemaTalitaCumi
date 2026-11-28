<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-014 / 07.05/CA-010.
 *
 * No hay teléfono, tutor principal activo o prefijo configurado.
 */
final class WhatsappDiagnosticoNoDisponibleException extends AgendaTurnosDomainException
{
    public function __construct(public readonly string $motivo)
    {
        parent::__construct($motivo);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'WHATSAPP_DIAGNOSTICO_NO_DISPONIBLE';
    }
}
