<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;

/**
 * Snapshots estables para bitácora y eventos.
 */
final class AtencionSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function entrevista(AtencionEntrevista $entrevista): array
    {
        return [
            'tipo' => TipoAtencion::ENTREVISTA->value,
            'id' => (int) $entrevista->id,
            'paciente_id' => (int) $entrevista->paciente_id,
            'tutor_id' => (int) $entrevista->tutor_id,
            'sucursal_id' => (int) $entrevista->sucursal_id,
            'servicio_id' => (int) $entrevista->servicio_id,
            'tarifa_id' => (int) $entrevista->tarifa_id,
            'categoria_tarifa_id' => (int) $entrevista->categoria_tarifa_id,
            'fecha' => $entrevista->fecha->toDateString(),
            'monto_esperado' => (string) $entrevista->monto_esperado,
            'estado' => $entrevista->estado->value,
            'notas_admin' => $entrevista->notas_admin,
            'created_at' => $entrevista->created_at?->toIso8601String(),
            'updated_at' => $entrevista->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function diagnostico(AtencionDiagnostico $diagnostico): array
    {
        $diagnostico->loadMissing(['profesionales:id,nombre_completo', 'requisitos']);

        return [
            'tipo' => TipoAtencion::DIAGNOSTICO->value,
            'id' => (int) $diagnostico->id,
            'paciente_id' => (int) $diagnostico->paciente_id,
            'sucursal_id' => (int) $diagnostico->sucursal_id,
            'servicio_id' => (int) $diagnostico->servicio_id,
            'tipo_diagnostico_id' => (int) $diagnostico->tipo_diagnostico_id,
            'tarifa_id' => (int) $diagnostico->tarifa_id,
            'categoria_tarifa_id' => (int) $diagnostico->categoria_tarifa_id,
            'fecha_inicio' => $diagnostico->fecha_inicio->toDateString(),
            'fecha_fin' => $diagnostico->fecha_fin->toDateString(),
            'monto_esperado' => (string) $diagnostico->monto_esperado,
            'entrevista_previa_id' => $diagnostico->entrevista_previa_id === null
                ? null
                : (int) $diagnostico->entrevista_previa_id,
            'omitio_entrevista' => (bool) $diagnostico->omitio_entrevista,
            'motivo_omision_entrevista' => $diagnostico->motivo_omision_entrevista,
            'estado' => $diagnostico->estado->value,
            'notas_admin' => $diagnostico->notas_admin,
            'profesionales' => $diagnostico->profesionales
                ->map(static fn ($profesional): array => [
                    'id' => (int) $profesional->id,
                    'nombre_completo' => (string) $profesional->nombre_completo,
                ])
                ->values()
                ->all(),
            'requisitos' => $diagnostico->requisitos
                ->map(static fn ($requisito): array => [
                    'especialidad_id' => (int) $requisito->especialidad_id,
                    'especialidad_nombre' => (string) $requisito->especialidad_nombre,
                    'cantidad_minima' => (int) $requisito->cantidad_minima,
                ])
                ->values()
                ->all(),
            'created_at' => $diagnostico->created_at?->toIso8601String(),
            'updated_at' => $diagnostico->updated_at?->toIso8601String(),
        ];
    }
}
