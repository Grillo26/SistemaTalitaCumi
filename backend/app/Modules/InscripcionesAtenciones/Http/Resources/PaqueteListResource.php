<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Modules\InscripcionesAtenciones\Enums\DiaSemana;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Support\PaqueteAbilities;
use App\Modules\InscripcionesAtenciones\Support\PaquetePeriod;
use App\Modules\InscripcionesAtenciones\Support\PaqueteSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fila del listado de paquetes (RF-011).
 *
 * No expone observaciones ni teléfono del tutor.
 */
final class PaqueteListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PaqueteSesiones $paquete */
        $paquete = $this->resource;
        $user = $request->user();
        $estado = $paquete->estado->mostrado(
            $paquete->fecha_fin->toDateString(),
            PaquetePeriod::today(),
        );

        return [
            'id' => (int) $paquete->id,
            'paciente' => [
                'id' => (int) $paquete->paciente_id,
                'codigo' => $paquete->paciente_codigo,
                'nombre_completo' => $this->patientName($paquete),
                'estado' => $paquete->paciente_estado,
            ],
            'tutor' => [
                'id' => (int) $paquete->tutor_responsable_id,
                'nombre_completo' => (string) ($paquete->tutor_nombre ?? ''),
            ],
            'sucursal' => [
                'id' => (int) $paquete->sucursal_id,
                'nombre' => (string) ($paquete->sucursal_nombre ?? ''),
            ],
            'servicio' => [
                'id' => (int) $paquete->servicio_id,
                'nombre' => (string) ($paquete->servicio_nombre ?? ''),
            ],
            'categoria' => [
                'id' => (int) $paquete->categoria_tarifa_id,
                'nombre' => (string) ($paquete->categoria_tarifa_nombre ?? ''),
            ],
            'profesional' => $paquete->profesional_id === null
                ? null
                : [
                    'id' => (int) $paquete->profesional_id,
                    'nombre' => (string) ($paquete->profesional_nombre ?? ''),
                ],
            'cantidad_sesiones' => (int) $paquete->cantidad_sesiones,
            'dias_semana' => array_values($paquete->dias_semana),
            'dias_semana_labels' => array_map(
                static fn (int $dia): string => DiaSemana::labelFor($dia),
                $paquete->dias_semana,
            ),
            'hora' => PaqueteSnapshot::horaCorta((string) $paquete->hora),
            'duracion_minutos' => (int) $paquete->duracion_minutos,
            'fecha_inicio' => $paquete->fecha_inicio->toDateString(),
            'fecha_fin' => $paquete->fecha_fin->toDateString(),
            'monto_esperado' => (string) $paquete->monto_esperado,
            'estado' => $paquete->estado->value,
            'estado_mostrado' => $estado->value,
            'estado_label' => $estado->label(),
            'paquete_origen_id' => $paquete->paquete_origen_id,
            'created_at' => $paquete->created_at?->toIso8601String(),
            'updated_at' => $paquete->updated_at?->toIso8601String(),
            'can' => $user === null ? [] : PaqueteAbilities::forRow($user, $paquete),
        ];
    }

    private function patientName(PaqueteSesiones $paquete): string
    {
        return trim(implode(' ', array_filter([
            $paquete->paciente_nombre,
            $paquete->paciente_apellido_paterno,
            $paquete->paciente_apellido_materno,
        ], static fn (mixed $value): bool => $value !== null && $value !== '')));
    }
}
