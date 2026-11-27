<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResolverContract;
use App\Modules\InscripcionesAtenciones\Casts\DiasSemanaCast;
use App\Modules\InscripcionesAtenciones\Enums\EstadoPaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Events\PaqueteCreated;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteDuplicadoException;
use App\Modules\InscripcionesAtenciones\Exceptions\TarifaCambioException;
use App\Modules\InscripcionesAtenciones\Exceptions\TutorNoPerteneceAlPacienteException;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\CantidadSesionesRule;
use App\Modules\InscripcionesAtenciones\Rules\DiasHoraRule;
use App\Modules\InscripcionesAtenciones\Rules\DuracionSesionRule;
use App\Modules\InscripcionesAtenciones\Rules\MontoPaqueteRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteDatosObligatoriosRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteObservacionesRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteOverlapRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteVigenciaRule;
use App\Modules\InscripcionesAtenciones\Rules\TarifaVigenteRule;
use App\Modules\InscripcionesAtenciones\Support\PaqueteSettings;
use App\Modules\InscripcionesAtenciones\Support\PaqueteSnapshot;
use App\Modules\InscripcionesAtenciones\Support\ProfesionalPaqueteGuard;
use App\Modules\InscripcionesAtenciones\Support\TerapiaIndividualServicioGuard;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Exceptions\TutorInactiveException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * [Paquetes Sesiones Individuales] RF-001, RF-002, RF-003, RF-004, RF-005,
 * RF-006, RF-007, RF-008, RN-001 a RN-003, RN-006, RN-008.
 */
final class CreatePaqueteSesionesAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly PatientDirectoryContract $patients,
        private readonly TutorDirectoryContract $tutors,
        private readonly TarifaAplicableResolverContract $tarifas,
        private readonly TerapiaIndividualServicioGuard $servicios,
        private readonly ProfesionalPaqueteGuard $profesionales,
        private readonly PaqueteSettings $settings,
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
    ): PaqueteSesiones {
        $pacienteId = (int) $data['paciente_id'];
        $tutorId = (int) $data['tutor_responsable_id'];
        $sucursalId = (int) $data['sucursal_id'];
        $servicioId = (int) $data['servicio_id'];
        $profesionalId = isset($data['profesional_id']) && $data['profesional_id'] !== ''
            ? (int) $data['profesional_id']
            : null;
        $categoriaId = (int) $data['categoria_tarifa_id'];
        $fechaInicio = (string) $data['fecha_inicio'];
        $fechaFin = (string) $data['fecha_fin'];
        $observaciones = $data['observaciones'] ?? null;

        $cantidad = isset($data['cantidad_sesiones']) && $data['cantidad_sesiones'] !== ''
            ? CantidadSesionesRule::assertValida($data['cantidad_sesiones'])
            : $this->settings->cantidadSesionesDefault();

        $duracion = isset($data['duracion_minutos']) && $data['duracion_minutos'] !== ''
            ? DuracionSesionRule::assertValida($data['duracion_minutos'])
            : $this->settings->duracionMinutosDefault();

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

        $this->servicios->assertServicioValido($servicioId);
        $this->profesionales->assertDisponible($profesionalId, $sucursalId);

        $tarifa = $this->tarifas->resolver($servicioId, $sucursalId, $categoriaId, $fechaInicio);
        TarifaVigenteRule::assertVigente($tarifa, $fechaInicio);
        PaqueteVigenciaRule::assertValido($fechaInicio, $fechaFin);
        PaqueteObservacionesRule::assertValidas(is_string($observaciones) ? $observaciones : null);

        $tarifaIdEsperada = isset($data['tarifa_id_esperada'])
            ? (int) $data['tarifa_id_esperada']
            : null;
        $confirmarCambio = (bool) ($data['confirmar_tarifa_cambio'] ?? false);

        if ($tarifaIdEsperada !== null
            && $tarifaIdEsperada !== $tarifa->tarifaId
            && ! $confirmarCambio) {
            throw new TarifaCambioException($tarifa->toArray());
        }

        $diasCsv = DiasHoraRule::assertDiasValidos((array) ($data['dias_semana'] ?? []));
        $hora = DiasHoraRule::assertHoraValida((string) $data['hora']);
        $dias = DiasSemanaCast::normalize(explode(',', $diasCsv));

        $montoTotal = MontoPaqueteRule::calcular($tarifa->monto, $cantidad);

        PaqueteDatosObligatoriosRule::assertCompletos([
            'paciente_id' => $pacienteId,
            'tutor_responsable_id' => $tutorId,
            'sucursal_id' => $sucursalId,
            'servicio_id' => $servicioId,
            'tarifa_id' => $tarifa->tarifaId,
            'categoria_tarifa_id' => $categoriaId,
            'cantidad_sesiones' => $cantidad,
            'dias_semana' => $dias,
            'hora' => $hora,
            'duracion_minutos' => $duracion,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        try {
            return DB::transaction(function () use (
                $pacienteId,
                $tutorId,
                $sucursalId,
                $servicioId,
                $profesionalId,
                $categoriaId,
                $tarifa,
                $cantidad,
                $diasCsv,
                $dias,
                $hora,
                $duracion,
                $fechaInicio,
                $fechaFin,
                $montoTotal,
                $observaciones,
                $actor,
                $ip,
                $dispatchCreated,
                $origenId,
            ): PaqueteSesiones {
                $periodos = PaqueteSesiones::query()
                    ->where('paciente_id', $pacienteId)
                    ->where('servicio_id', $servicioId)
                    ->whereIn('estado', [
                        EstadoPaqueteSesiones::ACTIVO->value,
                        EstadoPaqueteSesiones::EN_PAUSA->value,
                    ])
                    ->lockForUpdate()
                    ->get(['id', 'fecha_inicio', 'fecha_fin'])
                    ->map(static fn (PaqueteSesiones $paquete): array => [
                        'id' => (int) $paquete->id,
                        'fecha_inicio' => $paquete->fecha_inicio->toDateString(),
                        'fecha_fin' => $paquete->fecha_fin->toDateString(),
                    ])
                    ->all();

                PaqueteOverlapRule::assertSinSolapamiento($periodos, $fechaInicio, $fechaFin);

                $paquete = PaqueteSesiones::query()->create([
                    'paciente_id' => $pacienteId,
                    'tutor_responsable_id' => $tutorId,
                    'sucursal_id' => $sucursalId,
                    'servicio_id' => $servicioId,
                    'profesional_id' => $profesionalId,
                    'tarifa_id' => $tarifa->tarifaId,
                    'categoria_tarifa_id' => $categoriaId,
                    'cantidad_sesiones' => $cantidad,
                    'dias_semana' => $diasCsv,
                    'hora' => $hora,
                    'duracion_minutos' => $duracion,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'monto_esperado' => $montoTotal,
                    'estado' => EstadoPaqueteSesiones::ACTIVO->value,
                    'paquete_origen_id' => $origenId,
                    'observaciones' => is_string($observaciones) && $observaciones !== ''
                        ? $observaciones
                        : null,
                ]);

                $paquete->refresh();
                $snapshot = PaqueteSnapshot::from($paquete);

                if ($dispatchCreated) {
                    event(new PaqueteCreated(
                        paqueteId: (int) $paquete->id,
                        pacienteId: $pacienteId,
                        tutorResponsableId: $tutorId,
                        sucursalId: $sucursalId,
                        servicioId: $servicioId,
                        profesionalId: $profesionalId,
                        tarifaId: $tarifa->tarifaId,
                        categoriaTarifaId: $categoriaId,
                        cantidadSesiones: $cantidad,
                        diasSemana: $dias,
                        hora: $hora,
                        duracionMinutos: $duracion,
                        fechaInicio: $fechaInicio,
                        fechaFin: $fechaFin,
                        montoEsperado: $montoTotal,
                        paqueteOrigenId: $origenId,
                        actorId: $actor->id,
                        ip: $ip,
                        snapshot: $snapshot,
                    ));
                }

                return $paquete;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23P01'
                || str_contains($exception->getMessage(), 'paquete_sesiones_sin_solapamiento')) {
                throw new PaqueteDuplicadoException([
                    'paciente_id' => $pacienteId,
                    'servicio_id' => $servicioId,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                ]);
            }

            throw $exception;
        }
    }
}
