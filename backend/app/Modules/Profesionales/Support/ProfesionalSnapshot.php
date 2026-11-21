<?php

namespace App\Modules\Profesionales\Support;

use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Models\Profesional;

/**
 * Snapshot seguro del profesional para bitácora.
 *
 * Whitelist de datos de negocio. Nunca datos clínicos ni credenciales.
 */
final class ProfesionalSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Profesional $profesional): array
    {
        return [
            'id' => $profesional->id,
            'nombre_completo' => $profesional->nombre_completo,
            'ci' => $profesional->ci,
            'telefono' => $profesional->telefono,
            'telefono_2' => $profesional->telefono_2,
            'email' => $profesional->email,
            'matricula' => $profesional->matricula,
            'activo' => (bool) $profesional->activo,
            'motivo_estado' => $profesional->motivo_estado,
            'estado_cambiado_en' => $profesional->estado_cambiado_en?->toIso8601String(),
            'version' => $profesional->version,
            'updated_at' => $profesional->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Relaciones activas de especialidad ordenadas por tipo y nombre.
     *
     * @return list<array{especialidad_id: int, nombre: string, tipo: string, activo: bool}>
     */
    public static function especialidades(Profesional $profesional): array
    {
        $profesional->loadMissing('relacionesEspecialidad.especialidad');

        return $profesional->relacionesEspecialidad
            ->filter(static fn ($relacion): bool => (bool) $relacion->activo)
            ->sortBy(static fn ($relacion): string => sprintf(
                '%d-%s',
                $relacion->tipo === TipoEspecialidad::PRINCIPAL ? 0 : 1,
                mb_strtolower((string) $relacion->especialidad?->nombre),
            ))
            ->map(static fn ($relacion): array => [
                'especialidad_id' => (int) $relacion->especialidad_id,
                'nombre' => (string) $relacion->especialidad?->nombre,
                'tipo' => $relacion->tipo->value,
                'activo' => (bool) $relacion->activo,
            ])
            ->values()
            ->all();
    }
}
