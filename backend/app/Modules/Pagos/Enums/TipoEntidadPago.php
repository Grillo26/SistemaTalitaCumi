<?php

namespace App\Modules\Pagos\Enums;

/**
 * Entidades que pueden originar un pago (08.01/RN-007).
 *
 * Cada valor corresponde a una columna FK de `pago`. `venta_material` queda
 * reservado para 10.3: hoy responde 503 y nunca crea un pago.
 */
enum TipoEntidadPago: string
{
    case INSCRIPCION_SALA = 'inscripcion_sala';
    case PAQUETE_SESIONES = 'paquete_sesiones';
    case ATENCION_ENTREVISTA = 'atencion_entrevista';
    case ATENCION_DIAGNOSTICO = 'atencion_diagnostico';
    case VENTA_MATERIAL = 'venta_material';

    public function label(): string
    {
        return match ($this) {
            self::INSCRIPCION_SALA => 'Inscripción a sala grupal',
            self::PAQUETE_SESIONES => 'Paquete de sesiones',
            self::ATENCION_ENTREVISTA => 'Entrevista',
            self::ATENCION_DIAGNOSTICO => 'Diagnóstico',
            self::VENTA_MATERIAL => 'Venta de materiales',
        };
    }

    /**
     * Columna FK de `pago` que vincula esta entidad (RN-007).
     */
    public function columna(): ?string
    {
        return match ($this) {
            self::INSCRIPCION_SALA => 'inscripcion_sala_id',
            self::PAQUETE_SESIONES => 'paquete_sesiones_id',
            self::ATENCION_ENTREVISTA => 'atencion_entrevista_id',
            self::ATENCION_DIAGNOSTICO => 'atencion_diagnostico_id',
            self::VENTA_MATERIAL => null,
        };
    }

    public function disponibleEnEtapa(): bool
    {
        return $this !== self::VENTA_MATERIAL;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $tipo): string => $tipo->value, self::cases());
    }
}
