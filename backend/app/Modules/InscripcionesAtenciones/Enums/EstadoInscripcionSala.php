<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Estados de una inscripción a sala grupal.
 *
 * El schema admite cinco valores. Este submódulo solo escribe `activa` y
 * `retirada`; `en_pausa`, `vencida` y `renovada` quedan reservados para el
 * submódulo de estados/pausa y para la lectura derivada.
 */
enum EstadoInscripcionSala: string
{
    case ACTIVA = 'activa';
    case EN_PAUSA = 'en_pausa';
    case VENCIDA = 'vencida';
    case RENOVADA = 'renovada';
    case RETIRADA = 'retirada';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVA => 'Activa',
            self::EN_PAUSA => 'En pausa',
            self::VENCIDA => 'Vencida',
            self::RENOVADA => 'Renovada',
            self::RETIRADA => 'Retirada',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $estado): string => $estado->value, self::cases());
    }

    public function permiteEdicion(): bool
    {
        return $this === self::ACTIVA;
    }

    public function permiteRenovacion(): bool
    {
        return $this === self::ACTIVA;
    }

    public function permiteBaja(): bool
    {
        return $this === self::ACTIVA;
    }

    /**
     * Estado visible en la UI: una inscripción activa con fecha de fin pasada
     * se muestra como vencida, sin modificar el valor almacenado.
     */
    public function mostrado(?string $fechaFin, string $hoy): self
    {
        if ($this === self::ACTIVA && $fechaFin !== null && $fechaFin < $hoy) {
            return self::VENCIDA;
        }

        return $this;
    }
}
