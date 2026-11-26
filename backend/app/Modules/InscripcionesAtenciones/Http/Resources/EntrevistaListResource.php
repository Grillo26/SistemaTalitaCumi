<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\InscripcionesAtenciones\Support\AtencionAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fila del listado de entrevistas (RF-008).
 */
final class EntrevistaListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AtencionEntrevista $entrevista */
        $entrevista = $this->resource;
        $user = $request->user();

        return [
            'id' => (int) $entrevista->id,
            'paciente' => [
                'id' => (int) $entrevista->paciente_id,
                'codigo' => $entrevista->paciente_codigo,
                'nombre_completo' => $this->patientName($entrevista),
                'estado' => $entrevista->paciente_estado,
            ],
            'tutor' => [
                'id' => (int) $entrevista->tutor_id,
                'nombre_completo' => (string) ($entrevista->tutor_nombre ?? ''),
            ],
            'sucursal' => [
                'id' => (int) $entrevista->sucursal_id,
                'nombre' => (string) ($entrevista->sucursal_nombre ?? ''),
            ],
            'servicio' => [
                'id' => (int) $entrevista->servicio_id,
                'nombre' => (string) ($entrevista->servicio_nombre ?? ''),
                'tipo_servicio' => $entrevista->tipo_servicio_nombre !== null
                    ? (string) $entrevista->tipo_servicio_nombre
                    : null,
            ],
            'categoria' => [
                'id' => (int) $entrevista->categoria_tarifa_id,
                'nombre' => (string) ($entrevista->categoria_tarifa_nombre ?? ''),
            ],
            'fecha' => $entrevista->fecha->toDateString(),
            'monto_esperado' => (string) $entrevista->monto_esperado,
            'estado' => $entrevista->estado->value,
            'estado_label' => $entrevista->estado->label(),
            'created_at' => $entrevista->created_at?->toIso8601String(),
            'updated_at' => $entrevista->updated_at?->toIso8601String(),
            'can' => $user === null
                ? []
                : AtencionAbilities::forRow($user, $entrevista->estado),
        ];
    }

    private function patientName(AtencionEntrevista $entrevista): string
    {
        return trim(implode(' ', array_filter([
            $entrevista->paciente_nombre,
            $entrevista->paciente_apellido_paterno,
            $entrevista->paciente_apellido_materno,
        ], static fn (mixed $value): bool => $value !== null && $value !== '')));
    }
}
