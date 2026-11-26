<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Support\InscripcionAbilities;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fila del listado de inscripciones (RF-007).
 *
 * No expone observaciones, teléfono del tutor ni datos clínicos.
 */
final class InscripcionListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var InscripcionSala $inscripcion */
        $inscripcion = $this->resource;
        $user = $request->user();
        $estadoMostrado = $inscripcion->estado->mostrado(
            $inscripcion->fecha_fin->toDateString(),
            InscripcionPeriod::today(),
        );

        return [
            'id' => (int) $inscripcion->id,
            'paciente' => [
                'id' => (int) $inscripcion->paciente_id,
                'codigo' => $inscripcion->paciente_codigo,
                'nombre_completo' => $this->patientName($inscripcion),
                'estado' => $inscripcion->paciente_estado,
            ],
            'tutor' => [
                'id' => (int) $inscripcion->tutor_responsable_id,
                'nombre_completo' => (string) ($inscripcion->tutor_nombre ?? ''),
            ],
            'sucursal' => [
                'id' => (int) $inscripcion->sucursal_id,
                'nombre' => (string) ($inscripcion->sucursal_nombre ?? ''),
            ],
            'sala' => [
                'id' => (int) $inscripcion->sala_id,
                'nombre' => (string) ($inscripcion->sala_nombre ?? ''),
                'tipo_sala' => $inscripcion->tipo_sala_nombre,
                'turno' => $inscripcion->turno_nombre,
            ],
            'servicio' => [
                'id' => (int) $inscripcion->servicio_id,
                'nombre' => (string) ($inscripcion->servicio_nombre ?? ''),
            ],
            'categoria' => [
                'id' => (int) $inscripcion->categoria_tarifa_id,
                'nombre' => (string) ($inscripcion->categoria_tarifa_nombre ?? ''),
            ],
            'fecha_inicio' => $inscripcion->fecha_inicio->toDateString(),
            'fecha_fin' => $inscripcion->fecha_fin->toDateString(),
            'monto_esperado' => (string) $inscripcion->monto_esperado,
            'estado' => $inscripcion->estado->value,
            'estado_mostrado' => $estadoMostrado->value,
            'estado_label' => $estadoMostrado->label(),
            'inscripcion_origen_id' => $inscripcion->inscripcion_origen_id,
            'created_at' => $inscripcion->created_at?->toIso8601String(),
            'updated_at' => $inscripcion->updated_at?->toIso8601String(),
            'can' => $user === null
                ? []
                : InscripcionAbilities::forRow($user, $inscripcion),
        ];
    }

    private function patientName(InscripcionSala $inscripcion): string
    {
        return trim(implode(' ', array_filter([
            $inscripcion->paciente_nombre,
            $inscripcion->paciente_apellido_paterno,
            $inscripcion->paciente_apellido_materno,
        ], static fn (mixed $value): bool => $value !== null && $value !== '')));
    }
}
