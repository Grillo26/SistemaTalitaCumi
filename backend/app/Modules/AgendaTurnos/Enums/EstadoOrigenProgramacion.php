<?php

namespace App\Modules\AgendaTurnos\Enums;

/**
 * Estado efectivo de la entidad origen de una programación de turnos.
 *
 * Normaliza los estados de `inscripcion_sala` y `paquete_sesiones`, que usan
 * valores distintos para el mismo concepto (activa/activo, retirada/retirado).
 */
enum EstadoOrigenProgramacion: string
{
    case ACTIVA = 'activa';
    case EN_PAUSA = 'en_pausa';
    case VENCIDA = 'vencida';
    case RENOVADA = 'renovada';
    case RETIRADA = 'retirada';
    case DESCONOCIDO = 'desconocido';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVA => 'Activa',
            self::EN_PAUSA => 'En pausa',
            self::VENCIDA => 'Vencida',
            self::RENOVADA => 'Renovada',
            self::RETIRADA => 'Retirada',
            self::DESCONOCIDO => 'Desconocida',
        };
    }

    public static function desdePersistido(string $estado): self
    {
        return match ($estado) {
            'activa', 'activo' => self::ACTIVA,
            'en_pausa' => self::EN_PAUSA,
            'vencida', 'vencido' => self::VENCIDA,
            'renovada', 'renovado' => self::RENOVADA,
            'retirada', 'retirado' => self::RETIRADA,
            default => self::DESCONOCIDO,
        };
    }
}
