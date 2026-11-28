<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.04/RF-004, 07.04/RF-005, 07.04/RN-004, 07.04/CA-006.
 *
 * Recepción intenta corregir fuera de la ventana sin el permiso especial.
 */
final class AsistenciaFueraDeVentanaException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly int $horasVentana,
        public readonly ?string $venceEn,
    ) {
        parent::__construct(
            'La ventana de edición de asistencia ya venció. Contactá a Administración para corregirla.',
        );
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'ASISTENCIA_FUERA_DE_VENTANA';
    }

    public function context(): array
    {
        return [
            'horas_ventana' => $this->horasVentana,
            'vence_en' => $this->venceEn,
        ];
    }
}
