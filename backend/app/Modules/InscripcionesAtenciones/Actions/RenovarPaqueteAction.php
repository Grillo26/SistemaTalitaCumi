<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Events\PaqueteRenewed;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteYaRenovadoException;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\PaquetePeriod;
use App\Modules\InscripcionesAtenciones\Support\PaqueteSnapshot;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use Illuminate\Support\Facades\DB;

/**
 * [Paquetes Sesiones Individuales] RF-013 / CU-02.
 */
final class RenovarPaqueteAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly PatientDirectoryContract $patients,
        private readonly CreatePaqueteSesionesAction $create,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(int $id, array $data, User $actor, ?string $ip = null): PaqueteSesiones
    {
        return DB::transaction(function () use ($id, $data, $actor, $ip): PaqueteSesiones {
            $origen = PaqueteSesiones::query()->lockForUpdate()->find($id);

            if ($origen === null) {
                throw new PaqueteNotFoundException($id);
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $origen->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            PaqueteStateTransitionRule::assertPuedeRenovar($origen->estado);

            $existente = PaqueteSesiones::query()
                ->where('paquete_origen_id', $id)
                ->where('estado', '!=', 'retirado')
                ->latest('id')
                ->first();

            if ($existente !== null) {
                throw new PaqueteYaRenovadoException((int) $existente->id);
            }

            $this->patients->asegurarPermiteInscripcion((int) $origen->paciente_id);

            $fechaInicio = isset($data['fecha_inicio'])
                ? (string) $data['fecha_inicio']
                : PaquetePeriod::nextStart($origen->fecha_fin->toDateString());

            $fechaFin = isset($data['fecha_fin'])
                ? (string) $data['fecha_fin']
                : PaquetePeriod::sameDurationEnd(
                    $fechaInicio,
                    $origen->fecha_inicio->toDateString(),
                    $origen->fecha_fin->toDateString(),
                );

            $payload = [
                'paciente_id' => (int) $origen->paciente_id,
                'tutor_responsable_id' => (int) $origen->tutor_responsable_id,
                'sucursal_id' => (int) $origen->sucursal_id,
                'servicio_id' => (int) $origen->servicio_id,
                'profesional_id' => array_key_exists('profesional_id', $data)
                    ? $data['profesional_id']
                    : $origen->profesional_id,
                'categoria_tarifa_id' => (int) $origen->categoria_tarifa_id,
                'cantidad_sesiones' => isset($data['cantidad_sesiones'])
                    ? $data['cantidad_sesiones']
                    : (int) $origen->cantidad_sesiones,
                'dias_semana' => isset($data['dias_semana'])
                    ? (array) $data['dias_semana']
                    : $origen->dias_semana,
                'hora' => isset($data['hora'])
                    ? (string) $data['hora']
                    : PaqueteSnapshot::horaCorta((string) $origen->hora),
                'duracion_minutos' => isset($data['duracion_minutos'])
                    ? $data['duracion_minutos']
                    : (int) $origen->duracion_minutos,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'observaciones' => $data['observaciones'] ?? null,
                'tarifa_id_esperada' => $data['tarifa_id_esperada'] ?? null,
                'confirmar_tarifa_cambio' => (bool) ($data['confirmar_tarifa_cambio'] ?? false),
            ];

            $nuevo = ($this->create)(
                data: $payload,
                actor: $actor,
                ip: $ip,
                dispatchCreated: false,
                origenId: (int) $origen->id,
            );

            event(new PaqueteRenewed(
                paqueteId: (int) $nuevo->id,
                paqueteOrigenId: (int) $origen->id,
                pacienteId: (int) $nuevo->paciente_id,
                tutorResponsableId: (int) $nuevo->tutor_responsable_id,
                sucursalId: (int) $nuevo->sucursal_id,
                servicioId: (int) $nuevo->servicio_id,
                profesionalId: $nuevo->profesional_id,
                tarifaId: (int) $nuevo->tarifa_id,
                categoriaTarifaId: (int) $nuevo->categoria_tarifa_id,
                cantidadSesiones: (int) $nuevo->cantidad_sesiones,
                diasSemana: $nuevo->dias_semana,
                hora: PaqueteSnapshot::horaCorta((string) $nuevo->hora),
                duracionMinutos: (int) $nuevo->duracion_minutos,
                fechaInicio: $nuevo->fecha_inicio->toDateString(),
                fechaFin: $nuevo->fecha_fin->toDateString(),
                montoEsperado: (string) $nuevo->monto_esperado,
                actorId: $actor->id,
                ip: $ip,
                before: PaqueteSnapshot::from($origen),
                after: PaqueteSnapshot::from($nuevo),
            ));

            return $nuevo;
        });
    }
}
