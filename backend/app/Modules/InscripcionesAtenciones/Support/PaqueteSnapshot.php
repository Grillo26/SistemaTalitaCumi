<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;

/**
 * Snapshot estable para bitácora y eventos.
 */
final class PaqueteSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(PaqueteSesiones $paquete): array
    {
        return [
            'id' => (int) $paquete->id,
            'paciente_id' => (int) $paquete->paciente_id,
            'tutor_responsable_id' => (int) $paquete->tutor_responsable_id,
            'sucursal_id' => (int) $paquete->sucursal_id,
            'servicio_id' => (int) $paquete->servicio_id,
            'profesional_id' => $paquete->profesional_id === null
                ? null
                : (int) $paquete->profesional_id,
            'tarifa_id' => (int) $paquete->tarifa_id,
            'categoria_tarifa_id' => (int) $paquete->categoria_tarifa_id,
            'cantidad_sesiones' => (int) $paquete->cantidad_sesiones,
            'dias_semana' => array_values($paquete->dias_semana),
            'hora' => self::horaCorta((string) $paquete->hora),
            'duracion_minutos' => (int) $paquete->duracion_minutos,
            'fecha_inicio' => $paquete->fecha_inicio->toDateString(),
            'fecha_fin' => $paquete->fecha_fin->toDateString(),
            'monto_esperado' => (string) $paquete->monto_esperado,
            'estado' => $paquete->estado->value,
            'paquete_origen_id' => $paquete->paquete_origen_id === null
                ? null
                : (int) $paquete->paquete_origen_id,
            'observaciones' => $paquete->observaciones,
            'created_at' => $paquete->created_at?->toIso8601String(),
            'updated_at' => $paquete->updated_at?->toIso8601String(),
        ];
    }

    public static function horaCorta(string $hora): string
    {
        return substr($hora, 0, 5);
    }
}
