<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Estados de un paquete de sesiones individuales.
 *
 * El schema admite cinco valores. Este submódulo solo escribe `activo` y
 * `retirado`; `vencido` se deriva de la fecha de fin. `en_pausa` y `renovado`
 * quedan reservados para otros submódulos.
 */
enum EstadoPaqueteSesiones: string
{
    case ACTIVO = 'activo';
    case EN_PAUSA = 'en_pausa';
    case VENCIDO = 'vencido';
    case RENOVADO = 'renovado';
    case RETIRADO = 'retirado';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVO => 'Activo',
            self::EN_PAUSA => 'En pausa',
            self::VENCIDO => 'Vencido',
            self::RENOVADO => 'Renovado',
            self::RETIRADO => 'Retirado',
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
        return $this === self::ACTIVO;
    }

    public function permiteRenovacion(): bool
    {
        return $this === self::ACTIVO;
    }

    public function permiteBaja(): bool
    {
        return $this === self::ACTIVO || $this === self::EN_PAUSA;
    }

    /**
     * Estado visible: un paquete activo con fecha de fin pasada se muestra
     * como vencido sin modificar el valor almacenado.
     */
    public function mostrado(?string $fechaFin, string $hoy): self
    {
        if ($this === self::ACTIVO && $fechaFin !== null && $fechaFin < $hoy) {
            return self::VENCIDO;
        }

        return $this;
    }
}
