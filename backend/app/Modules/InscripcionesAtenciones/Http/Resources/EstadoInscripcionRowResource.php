<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Rules\EstadoEfectivoInscripcionRule;
use App\Modules\InscripcionesAtenciones\Support\EstadoAbilities;
use App\Modules\InscripcionesAtenciones\Support\EstadoPermission;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * [Estados Paciente e Inscripción] RF-011 / RF-014 / CA-011 / CA-012.
 *
 * Solo datos administrativos; nunca datos clínicos.
 */
final class EstadoInscripcionRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var object $row */
        $row = $this->resource;
        /** @var User|null $user */
        $user = $request->user();
        $tipo = TipoEntidadVigencia::from((string) $row->tipo_entidad);
        $hoy = VigenciaPeriod::today();
        $almacenado = EstadoInscripcion::fromStored($tipo->value, (string) $row->estado_almacenado);
        $efectivo = EstadoEfectivoInscripcionRule::effective(
            almacenado: $almacenado,
            fechaFin: (string) $row->fecha_fin,
            hoy: $hoy,
            renovada: (bool) $row->renovada,
        );
        $canPause = $user !== null
            && EstadoAbilities::puedePausarInscripcion($user, $tipo)
            && $efectivo->permitePausa();
        $canReactivate = $user !== null
            && EstadoAbilities::puedeReactivarInscripcion($user, $tipo)
            && $efectivo->permiteReactivacion();
        $canRetire = $user !== null
            && EstadoAbilities::puedeRetirarInscripcion($user, $tipo)
            && $efectivo->permiteRetiro();

        return [
            'tipo_entidad' => $tipo->value,
            'tipo_entidad_label' => $tipo->label(),
            'id' => (int) $row->id,
            'paciente' => [
                'id' => (int) $row->paciente_id,
                'codigo' => $row->paciente_codigo,
                'nombre_completo' => $this->patientName($row),
                'estado' => (string) $row->paciente_estado,
            ],
            'tutor' => [
                'id' => (int) $row->tutor_responsable_id,
                'nombre_completo' => (string) ($row->tutor_nombre ?? ''),
            ],
            'sucursal' => [
                'id' => (int) $row->sucursal_id,
                'nombre' => (string) ($row->sucursal_nombre ?? ''),
            ],
            'servicio' => [
                'id' => (int) $row->servicio_id,
                'nombre' => (string) ($row->servicio_nombre ?? ''),
            ],
            'sala' => $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
                ? [
                    'id' => (int) $row->sala_id,
                    'nombre' => (string) ($row->sala_nombre ?? ''),
                ]
                : null,
            'profesional' => $tipo === TipoEntidadVigencia::PAQUETE_SESIONES && $row->profesional_id !== null
                ? [
                    'id' => (int) $row->profesional_id,
                    'nombre_completo' => (string) ($row->profesional_nombre ?? ''),
                ]
                : null,
            'cantidad_sesiones' => $row->cantidad_sesiones === null
                ? null
                : (int) $row->cantidad_sesiones,
            'fecha_inicio' => (string) $row->fecha_inicio,
            'fecha_fin' => (string) $row->fecha_fin,
            'dias_para_vencer' => EstadoEfectivoInscripcionRule::diasHastaVencimiento(
                (string) $row->fecha_fin,
                $hoy,
            ),
            'monto_esperado' => (string) $row->monto_esperado,
            'estado_almacenado' => $almacenado->value,
            'estado_almacenado_label' => $almacenado->label(),
            'estado_efectivo' => $efectivo->value,
            'estado_efectivo_label' => $efectivo->label(),
            'renovada' => (bool) $row->renovada,
            'en_pausa' => (bool) $row->en_pausa,
            'retirada' => (bool) $row->retirada,
            'can' => [
                'view' => $user?->can(EstadoPermission::viewFor($tipo)) === true,
                'pause' => $canPause,
                'reactivate' => $canReactivate,
                'retire' => $canRetire,
                'history' => $user?->can(EstadoPermission::VIEW) === true,
            ],
        ];
    }

    private function patientName(object $row): string
    {
        return trim(implode(' ', array_filter([
            $row->paciente_nombre ?? null,
            $row->paciente_apellido_paterno ?? null,
            $row->paciente_apellido_materno ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '')));
    }
}
