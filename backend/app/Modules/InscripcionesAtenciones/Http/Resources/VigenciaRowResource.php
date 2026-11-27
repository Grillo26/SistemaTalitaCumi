<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Rules\RenovacionElegibleRule;
use App\Modules\InscripcionesAtenciones\Support\VigenciaAbilities;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPeriod;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPermission;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPresenter;
use App\Modules\InscripcionesAtenciones\Support\VigenciaSettings;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * [Vigencia y Renovación] RF-002 / RF-004 / RF-014 / RNF-005.
 *
 * Solo datos administrativos: paciente, tutor, servicio, período, estado y
 * saldo. Nunca datos clínicos.
 */
final class VigenciaRowResource extends JsonResource
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
        $ventana = app(VigenciaSettings::class)->ventanaAlertaDias();
        $hoy = VigenciaPeriod::today();
        $estadoPaciente = EstadoPaciente::tryFrom((string) $row->paciente_estado);
        $pacienteActivo = $estadoPaciente === EstadoPaciente::ACTIVO;
        $retirada = (bool) $row->retirada;
        $renovada = (bool) $row->renovada;
        $salaActiva = $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? (bool) ($row->sala_activo ?? false)
            : null;
        $motivo = RenovacionElegibleRule::motivo(
            pacienteActivo: $pacienteActivo,
            servicioActivo: (bool) $row->servicio_activo,
            salaActiva: $salaActiva,
            retirada: $retirada,
            renovada: $renovada,
        );
        $estado = VigenciaPresenter::estado(
            datos: [
                'fecha_fin' => (string) $row->fecha_fin,
                'retirada' => $retirada,
                'renovada' => $renovada,
            ],
            ventana: $ventana,
            hoy: $hoy,
        );
        $canViewContact = $user?->can(PacientesPermission::TUTOR_VIEW_CONTACT) ?? false;
        $telefono = $canViewContact ? (string) $row->tutor_telefono : null;
        $whatsappDisponible = $canViewContact
            && $telefono !== ''
            && $telefono !== null
            && (bool) $row->tutor_activo;

        return [
            'tipo_entidad' => $tipo->value,
            'tipo_entidad_label' => $tipo->label(),
            'id' => (int) $row->id,
            'paciente' => [
                'id' => (int) $row->paciente_id,
                'codigo' => $row->paciente_codigo,
                'nombre_completo' => trim(implode(' ', array_filter([
                    $row->paciente_nombre,
                    $row->paciente_apellido_paterno,
                    $row->paciente_apellido_materno,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''))),
                'estado' => $estadoPaciente?->value ?? (string) $row->paciente_estado,
                'estado_label' => $estadoPaciente?->label() ?? (string) $row->paciente_estado,
            ],
            'tutor' => [
                'id' => (int) $row->tutor_responsable_id,
                'nombre_completo' => (string) $row->tutor_nombre,
                'telefono' => $telefono,
                'telefono_enmascarado' => $canViewContact ? null : $this->maskPhone((string) $row->tutor_telefono),
                'whatsapp_disponible' => $whatsappDisponible,
            ],
            'sucursal' => [
                'id' => (int) $row->sucursal_id,
                'nombre' => (string) $row->sucursal_nombre,
            ],
            'servicio' => [
                'id' => (int) $row->servicio_id,
                'nombre' => (string) $row->servicio_nombre,
            ],
            'sala' => $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
                ? [
                    'id' => (int) $row->sala_id,
                    'nombre' => (string) $row->sala_nombre,
                ]
                : null,
            'profesional' => $tipo === TipoEntidadVigencia::PAQUETE_SESIONES && $row->profesional_id !== null
                ? [
                    'id' => (int) $row->profesional_id,
                    'nombre_completo' => (string) $row->profesional_nombre,
                ]
                : null,
            'cantidad_sesiones' => $row->cantidad_sesiones !== null
                ? (int) $row->cantidad_sesiones
                : null,
            'fecha_inicio' => (string) $row->fecha_inicio,
            'fecha_fin' => (string) $row->fecha_fin,
            'dias_para_vencer' => $estado['dias'],
            'monto_esperado' => (string) $row->monto_esperado,
            'estado_vigencia' => $estado['estado']->value,
            'estado_vigencia_label' => $estado['label'],
            'renovada' => $renovada,
            'retirada' => $retirada,
            'renovable' => $motivo === null,
            'motivo_no_renovable' => $motivo?->value,
            'motivo_no_renovable_label' => $motivo?->label(),
            'saldo' => $row->saldo ?? null,
            'saldo_disponible' => (bool) ($row->saldo_disponible ?? false),
            'can' => [
                'view' => $user?->can(VigenciaPermission::viewFor($tipo)) ?? false,
                'renew' => $user !== null
                    && VigenciaAbilities::puedeRenovar($user, $tipo)
                    && $motivo === null,
                'whatsapp' => $whatsappDisponible,
            ],
        ];
    }

    private function maskPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (strlen($digits) <= 5) {
            return '***';
        }

        return substr($digits, 0, 3).'***'.substr($digits, -2);
    }
}
