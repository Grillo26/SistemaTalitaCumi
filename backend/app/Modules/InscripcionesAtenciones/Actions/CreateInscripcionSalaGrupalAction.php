<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResolverContract;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcionSala;
use App\Modules\InscripcionesAtenciones\Events\InscripcionCreated;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionDuplicadaException;
use App\Modules\InscripcionesAtenciones\Exceptions\TarifaCambioException;
use App\Modules\InscripcionesAtenciones\Exceptions\TutorNoPerteneceAlPacienteException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionDatosObligatoriosRule;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionObservacionesRule;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionOverlapRule;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionPeriodoRule;
use App\Modules\InscripcionesAtenciones\Rules\TarifaVigenteRule;
use App\Modules\InscripcionesAtenciones\Support\InscripcionSnapshot;
use App\Modules\InscripcionesAtenciones\Support\SalaGrupalServicioGuard;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Exceptions\TutorInactiveException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * RF-001, RF-002, RF-003, RF-005, RF-006, RF-008, RN-001 a RN-004, RN-007,
 * RN-008, RN-011.
 */
final class CreateInscripcionSalaGrupalAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly PatientDirectoryContract $patients,
        private readonly TutorDirectoryContract $tutors,
        private readonly SalaDirectoryContract $salas,
        private readonly TarifaAplicableResolverContract $tarifas,
        private readonly SalaGrupalServicioGuard $servicios,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(
        array $data,
        User $actor,
        ?string $ip = null,
        bool $dispatchCreated = true,
        ?int $origenId = null,
    ): InscripcionSala {
        $pacienteId = (int) $data['paciente_id'];
        $tutorId = (int) $data['tutor_responsable_id'];
        $salaId = (int) $data['sala_id'];
        $sucursalId = (int) $data['sucursal_id'];
        $servicioId = (int) $data['servicio_id'];
        $categoriaId = (int) $data['categoria_tarifa_id'];
        $fechaInicio = (string) $data['fecha_inicio'];
        $fechaFin = (string) $data['fecha_fin'];
        $observaciones = $data['observaciones'] ?? null;

        if (! $this->access->puedeAcceder($actor->id, $sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        $this->branches->asegurarActiva($sucursalId);
        $this->patients->asegurarPermiteInscripcion($pacienteId);

        $tutores = $this->tutors->tutoresDePaciente($pacienteId);
        $tutor = null;

        foreach ($tutores as $candidate) {
            if ($candidate->id === $tutorId) {
                $tutor = $candidate;
                break;
            }
        }

        if ($tutor === null) {
            throw new TutorNoPerteneceAlPacienteException($tutorId, $pacienteId);
        }

        if (! $tutor->activo) {
            throw new TutorInactiveException;
        }

        $this->salas->asegurarActivaEnSucursal($salaId, $sucursalId);
        $this->servicios->assertServicioValido($servicioId);

        $tarifa = $this->tarifas->resolver($servicioId, $sucursalId, $categoriaId, $fechaInicio);
        TarifaVigenteRule::assertVigente($tarifa, $fechaInicio);
        InscripcionPeriodoRule::assertValido($fechaInicio, $fechaFin);
        InscripcionObservacionesRule::assertValidas(is_string($observaciones) ? $observaciones : null);

        $tarifaIdEsperada = isset($data['tarifa_id_esperada'])
            ? (int) $data['tarifa_id_esperada']
            : null;
        $confirmarCambio = (bool) ($data['confirmar_tarifa_cambio'] ?? false);

        if ($tarifaIdEsperada !== null
            && $tarifaIdEsperada !== $tarifa->tarifaId
            && ! $confirmarCambio) {
            throw new TarifaCambioException($tarifa->toArray());
        }

        InscripcionDatosObligatoriosRule::assertCompletos([
            'paciente_id' => $pacienteId,
            'tutor_responsable_id' => $tutorId,
            'sucursal_id' => $sucursalId,
            'sala_id' => $salaId,
            'servicio_id' => $servicioId,
            'tarifa_id' => $tarifa->tarifaId,
            'categoria_tarifa_id' => $categoriaId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        try {
            return DB::transaction(function () use (
                $pacienteId,
                $tutorId,
                $salaId,
                $sucursalId,
                $servicioId,
                $categoriaId,
                $tarifa,
                $fechaInicio,
                $fechaFin,
                $observaciones,
                $actor,
                $ip,
                $dispatchCreated,
                $origenId,
            ): InscripcionSala {
                $periodos = InscripcionSala::query()
                    ->where('paciente_id', $pacienteId)
                    ->where('sala_id', $salaId)
                    ->whereIn('estado', [
                        EstadoInscripcionSala::ACTIVA->value,
                        EstadoInscripcionSala::EN_PAUSA->value,
                    ])
                    ->lockForUpdate()
                    ->get(['id', 'fecha_inicio', 'fecha_fin'])
                    ->map(static fn (InscripcionSala $inscripcion): array => [
                        'id' => (int) $inscripcion->id,
                        'fecha_inicio' => $inscripcion->fecha_inicio->toDateString(),
                        'fecha_fin' => $inscripcion->fecha_fin->toDateString(),
                    ])
                    ->all();

                InscripcionOverlapRule::assertSinSolapamiento($periodos, $fechaInicio, $fechaFin);

                $inscripcion = InscripcionSala::query()->create([
                    'paciente_id' => $pacienteId,
                    'tutor_responsable_id' => $tutorId,
                    'sala_id' => $salaId,
                    'sucursal_id' => $sucursalId,
                    'servicio_id' => $servicioId,
                    'tarifa_id' => $tarifa->tarifaId,
                    'categoria_tarifa_id' => $categoriaId,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'monto_esperado' => $tarifa->monto,
                    'estado' => EstadoInscripcionSala::ACTIVA->value,
                    'inscripcion_origen_id' => $origenId,
                    'observaciones' => is_string($observaciones) && $observaciones !== ''
                        ? $observaciones
                        : null,
                ]);

                $inscripcion->refresh();
                $snapshot = InscripcionSnapshot::from($inscripcion);

                if ($dispatchCreated) {
                    event(new InscripcionCreated(
                        inscripcionId: (int) $inscripcion->id,
                        pacienteId: $pacienteId,
                        tutorResponsableId: $tutorId,
                        sucursalId: $sucursalId,
                        salaId: $salaId,
                        servicioId: $servicioId,
                        tarifaId: $tarifa->tarifaId,
                        categoriaTarifaId: $categoriaId,
                        fechaInicio: $fechaInicio,
                        fechaFin: $fechaFin,
                        montoEsperado: $tarifa->monto,
                        inscripcionOrigenId: $origenId,
                        actorId: $actor->id,
                        ip: $ip,
                        snapshot: $snapshot,
                    ));
                }

                return $inscripcion;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23P01'
                || str_contains($exception->getMessage(), 'inscripcion_sala_sin_solapamiento')) {
                throw new InscripcionDuplicadaException([
                    'paciente_id' => $pacienteId,
                    'sala_id' => $salaId,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                ]);
            }

            throw $exception;
        }
    }
}
