<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Motivos por los que una entidad no puede renovarse desde el panel.
 */
enum MotivoNoRenovable: string
{
    case PACIENTE_INACTIVO = 'paciente_inactivo';
    case SERVICIO_INACTIVO = 'servicio_inactivo';
    case SALA_INACTIVA = 'sala_inactiva';
    case ENTIDAD_RETIRADA = 'entidad_retirada';
    case ENTIDAD_RENOVADA = 'entidad_renovada';
    case SIN_PERMISO_RENOVAR = 'sin_permiso_renovar';

    public function label(): string
    {
        return match ($this) {
            self::PACIENTE_INACTIVO => 'El paciente no está activo. Reactivalo antes de renovar.',
            self::SERVICIO_INACTIVO => 'El servicio está inactivo. Cambialo o creá una entidad nueva.',
            self::SALA_INACTIVA => 'La sala está inactiva. Cambiala o creá una inscripción nueva.',
            self::ENTIDAD_RETIRADA => 'La entidad está retirada y no admite renovación.',
            self::ENTIDAD_RENOVADA => 'La entidad ya tiene una renovación activa.',
            self::SIN_PERMISO_RENOVAR => 'No tenés permiso para renovar este tipo de entidad.',
        };
    }
}
