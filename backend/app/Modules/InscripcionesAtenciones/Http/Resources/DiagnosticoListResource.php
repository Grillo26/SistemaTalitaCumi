<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Support\AtencionAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fila del listado de diagnósticos (RF-008 / CA-008).
 */
final class DiagnosticoListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AtencionDiagnostico $diagnostico */
        $diagnostico = $this->resource;
        $user = $request->user();

        return [
            'id' => (int) $diagnostico->id,
            'paciente' => [
                'id' => (int) $diagnostico->paciente_id,
                'codigo' => $diagnostico->paciente_codigo,
                'nombre_completo' => $this->patientName($diagnostico),
                'estado' => $diagnostico->paciente_estado,
            ],
            'sucursal' => [
                'id' => (int) $diagnostico->sucursal_id,
                'nombre' => (string) ($diagnostico->sucursal_nombre ?? ''),
            ],
            'servicio' => [
                'id' => (int) $diagnostico->servicio_id,
                'nombre' => (string) ($diagnostico->servicio_nombre ?? ''),
                'tipo_servicio' => $diagnostico->tipo_servicio_nombre !== null
                    ? (string) $diagnostico->tipo_servicio_nombre
                    : null,
            ],
            'tipo_diagnostico' => [
                'id' => (int) $diagnostico->tipo_diagnostico_id,
                'nombre' => (string) ($diagnostico->tipo_diagnostico_nombre ?? ''),
                'dias_estimados' => (int) ($diagnostico->tipo_diagnostico_dias ?? 1),
            ],
            'categoria' => [
                'id' => (int) $diagnostico->categoria_tarifa_id,
                'nombre' => (string) ($diagnostico->categoria_tarifa_nombre ?? ''),
            ],
            'fecha_inicio' => $diagnostico->fecha_inicio->toDateString(),
            'fecha_fin' => $diagnostico->fecha_fin->toDateString(),
            'monto_esperado' => (string) $diagnostico->monto_esperado,
            'estado' => $diagnostico->estado->value,
            'estado_label' => $diagnostico->estado->label(),
            'omitio_entrevista' => (bool) $diagnostico->omitio_entrevista,
            'motivo_omision_entrevista' => $diagnostico->motivo_omision_entrevista,
            'entrevista_previa_id' => $diagnostico->entrevista_previa_id,
            'profesionales' => $diagnostico->relationLoaded('profesionales')
                ? $diagnostico->profesionales
                    ->map(static fn ($profesional): array => [
                        'id' => (int) $profesional->id,
                        'nombre_completo' => (string) $profesional->nombre_completo,
                    ])
                    ->values()
                    ->all()
                : [],
            'created_at' => $diagnostico->created_at?->toIso8601String(),
            'updated_at' => $diagnostico->updated_at?->toIso8601String(),
            'can' => $user === null
                ? []
                : AtencionAbilities::forRow($user, $diagnostico->estado),
        ];
    }

    private function patientName(AtencionDiagnostico $diagnostico): string
    {
        return trim(implode(' ', array_filter([
            $diagnostico->paciente_nombre,
            $diagnostico->paciente_apellido_paterno,
            $diagnostico->paciente_apellido_materno,
        ], static fn (mixed $value): bool => $value !== null && $value !== '')));
    }
}
