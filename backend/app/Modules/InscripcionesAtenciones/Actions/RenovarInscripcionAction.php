<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Events\InscripcionRenewed;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionYaRenovadaException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPeriod;
use App\Modules\InscripcionesAtenciones\Support\InscripcionSnapshot;
use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Exceptions\TutorInactiveException;
use Illuminate\Support\Facades\DB;

/**
 * RF-010 / CU-02: renovación al siguiente período, vinculada al origen.
 */
final class RenovarInscripcionAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly PatientDirectoryContract $patients,
        private readonly TutorDirectoryContract $tutors,
        private readonly SalaDirectoryContract $salas,
        private readonly CreateInscripcionSalaGrupalAction $create,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(int $id, array $data, User $actor, ?string $ip = null): InscripcionSala
    {
        return DB::transaction(function () use ($id, $data, $actor, $ip): InscripcionSala {
            $origen = InscripcionSala::query()->lockForUpdate()->find($id);

            if ($origen === null) {
                throw new InscripcionNotFoundException($id);
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $origen->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            InscripcionStateTransitionRule::assertPuedeRenovar($origen->estado);

            $existente = InscripcionSala::query()
                ->where('inscripcion_origen_id', $id)
                ->where('estado', '!=', 'retirada')
                ->latest('id')
                ->first();

            if ($existente !== null) {
                throw new InscripcionYaRenovadaException((int) $existente->id);
            }

            $this->patients->asegurarPermiteInscripcion((int) $origen->paciente_id);
            $this->salas->asegurarActivaEnSucursal(
                (int) $origen->sala_id,
                (int) $origen->sucursal_id,
            );

            $tutor = $this->tutors->resumen((int) $origen->tutor_responsable_id);

            if (! $tutor->activo) {
                throw new TutorInactiveException;
            }

            $fechaInicio = isset($data['fecha_inicio'])
                ? (string) $data['fecha_inicio']
                : InscripcionPeriod::nextStart($origen->fecha_fin->toDateString());
            $fechaFin = isset($data['fecha_fin'])
                ? (string) $data['fecha_fin']
                : InscripcionPeriod::defaultEnd($fechaInicio);

            $payload = [
                'paciente_id' => (int) $origen->paciente_id,
                'tutor_responsable_id' => (int) $origen->tutor_responsable_id,
                'sala_id' => (int) $origen->sala_id,
                'sucursal_id' => (int) $origen->sucursal_id,
                'servicio_id' => isset($data['servicio_id'])
                    ? (int) $data['servicio_id']
                    : (int) $origen->servicio_id,
                'categoria_tarifa_id' => isset($data['categoria_tarifa_id'])
                    ? (int) $data['categoria_tarifa_id']
                    : (int) $origen->categoria_tarifa_id,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'observaciones' => $data['observaciones'] ?? null,
                'tarifa_id_esperada' => $data['tarifa_id_esperada'] ?? null,
                'confirmar_tarifa_cambio' => (bool) ($data['confirmar_tarifa_cambio'] ?? false),
            ];

            $nueva = ($this->create)(
                data: $payload,
                actor: $actor,
                ip: $ip,
                dispatchCreated: false,
                origenId: (int) $origen->id,
            );

            event(new InscripcionRenewed(
                inscripcionId: (int) $nueva->id,
                inscripcionOrigenId: (int) $origen->id,
                pacienteId: (int) $nueva->paciente_id,
                tutorResponsableId: (int) $nueva->tutor_responsable_id,
                sucursalId: (int) $nueva->sucursal_id,
                salaId: (int) $nueva->sala_id,
                servicioId: (int) $nueva->servicio_id,
                tarifaId: (int) $nueva->tarifa_id,
                categoriaTarifaId: (int) $nueva->categoria_tarifa_id,
                fechaInicio: $nueva->fecha_inicio->toDateString(),
                fechaFin: $nueva->fecha_fin->toDateString(),
                montoEsperado: (string) $nueva->monto_esperado,
                actorId: $actor->id,
                ip: $ip,
                before: InscripcionSnapshot::from($origen),
                after: InscripcionSnapshot::from($nueva),
            ));

            return $nueva;
        });
    }
}
