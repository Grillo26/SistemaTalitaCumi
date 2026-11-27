<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Estado de vigencia derivado de una inscripción o paquete.
 *
 * No se persiste: se calcula a partir de fecha_fin, estado, vínculo de
 * renovación y ventana de alerta.
 */
enum EstadoVigencia: string
{
    case VIGENTE = 'vigente';
    case POR_VENCER = 'por_vencer';
    case VENCIDO = 'vencido';
    case RENOVADO = 'renovado';
    case RETIRADO = 'retirado';

    public function label(): string
    {
        return match ($this) {
            self::VIGENTE => 'Vigente',
            self::POR_VENCER => 'Por vencer',
            self::VENCIDO => 'Vencido',
            self::RENOVADO => 'Renovado',
            self::RETIRADO => 'Retirado',
        };
    }

    public function requiereAtencion(): bool
    {
        return in_array($this, [self::POR_VENCER, self::VENCIDO], true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $estado): array => [
                'value' => $estado->value,
                'label' => $estado->label(),
            ],
            self::cases(),
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $estado): string => $estado->value, self::cases());
    }
}
