<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Modules\InscripcionesAtenciones\Support\AtencionAbilities;
use App\Modules\InscripcionesAtenciones\Support\AtencionDetalle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ficha de diagnóstico (RF-009). Sin resultados clínicos.
 */
final class DiagnosticoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AtencionDetalle $detalle */
        $detalle = $this->resource;
        $diagnostico = $detalle->atencion;
        $user = $request->user();

        return [
            'id' => (int) $diagnostico->id,
            'paciente' => [
                'id' => $detalle->paciente->id,
                'codigo' => $detalle->paciente->codigo,
                'nombre_completo' => $detalle->paciente->nombreCompleto,
                'fecha_nacimiento' => $detalle->paciente->fechaNacimiento,
                'edad_anios' => $detalle->paciente->edadAnios,
                'estado' => $detalle->paciente->estado->value,
                'estado_label' => $detalle->paciente->estado->label(),
            ],
            'sucursal' => $detalle->sucursal,
            'servicio' => $detalle->servicio,
            'tipo_diagnostico' => $detalle->tipoDiagnostico,
            'categoria' => $detalle->categoria,
            'fecha_inicio' => $diagnostico->fecha_inicio->toDateString(),
            'fecha_fin' => $diagnostico->fecha_fin->toDateString(),
            'monto_esperado' => (string) $diagnostico->monto_esperado,
            'tarifa_id' => (int) $diagnostico->tarifa_id,
            'estado' => $diagnostico->estado->value,
            'estado_label' => $diagnostico->estado->label(),
            'omitio_entrevista' => (bool) $diagnostico->omitio_entrevista,
            'motivo_omision_entrevista' => $diagnostico->motivo_omision_entrevista,
            'entrevista_previa_id' => $diagnostico->entrevista_previa_id,
            'entrevista_previa' => $detalle->entrevistaPrevia,
            'profesionales' => $detalle->profesionales,
            'requisitos' => $detalle->requisitos,
            'notas_admin' => $diagnostico->notas_admin,
            'retiro' => $detalle->retiro,
            'created_at' => $diagnostico->created_at?->toIso8601String(),
            'updated_at' => $diagnostico->updated_at?->toIso8601String(),
            'can' => $user === null
                ? []
                : array_merge(
                    AtencionAbilities::for($user),
                    AtencionAbilities::forRow($user, $diagnostico->estado),
                ),
        ];
    }
}
