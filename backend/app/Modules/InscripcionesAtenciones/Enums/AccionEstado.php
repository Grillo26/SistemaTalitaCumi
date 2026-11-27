<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * [Estados Paciente e Inscripción] RF-004 / RN-003.
 */
enum AccionEstado: string
{
    case PAUSAR = 'pausar';
    case REACTIVAR = 'reactivar';
    case RETIRAR = 'retirar';

    public function label(): string
    {
        return match ($this) {
            self::PAUSAR => 'Pausar',
            self::REACTIVAR => 'Reactivar',
            self::RETIRAR => 'Retirar',
        };
    }

    public function estadoDestino(): EstadoInscripcion
    {
        return match ($this) {
            self::PAUSAR => EstadoInscripcion::EN_PAUSA,
            self::REACTIVAR => EstadoInscripcion::ACTIVA,
            self::RETIRAR => EstadoInscripcion::RETIRADA,
        };
    }

    public function requiereMotivo(): bool
    {
        return $this === self::PAUSAR || $this === self::RETIRAR;
    }
}
