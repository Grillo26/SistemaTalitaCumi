<?php

namespace App\Modules\AgendaTurnos\Actions\Diagnosticos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\TurnoDiagnosticoProgramado;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoFueraDePeriodoException;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoNoCoordinableException;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoNotFoundException;
use App\Modules\AgendaTurnos\Exceptions\DisponibilidadDiagnosticoException;
use App\Modules\AgendaTurnos\Exceptions\ProfesionalNoAsignadoAlDiagnosticoException;
use App\Modules\AgendaTurnos\Exceptions\ProfesionalNoCubreRequisitoException;
use App\Modules\AgendaTurnos\Exceptions\TurnoDiagnosticoDuplicadoException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\DiasHabilitadosRule;
use App\Modules\AgendaTurnos\Rules\DuracionTurnoRule;
use App\Modules\AgendaTurnos\Rules\HorarioAgendaRule;
use App\Modules\AgendaTurnos\Rules\IntervaloMinimoRule;
use App\Modules\AgendaTurnos\Rules\TurnoSolapamientoRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\DiagnosticoClock;
use App\Modules\AgendaTurnos\Support\DiagnosticoEstadoSincronizador;
use App\Modules\AgendaTurnos\Support\DiagnosticoParametros;
use App\Modules\AgendaTurnos\Support\ProgramacionGuard;
use App\Modules\AgendaTurnos\Support\TurnoCandidato;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use App\Modules\AgendaTurnos\Support\TurnoLock;
use App\Modules\AgendaTurnos\Support\TurnosExistentesResolver;
use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosDirectoryContract;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionContract;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionResumen;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Profesionales\Contracts\ProfessionalScheduleContract;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * 07.05/RF-002 / 07.05/RF-003 / 07.05/RF-011 / 07.05/RN-001 / 07.05/RN-002 /
 * 07.05/RN-003 / 07.05/RN-009 / 07.05/RN-010 / 07.05/CU-01 / 07.05/CA-001 /
 * 07.05/CA-004 / 07.05/CA-009.
 *
 * Crea un turno individual para un profesional ya asociado al diagnóstico.
 * No crea diagnósticos, no asigna profesionales y no reimplementa 07.2/07.3/
 * 07.4. Una transacción: lock del diagnóstico, validaciones, creación, estado
 * general y bitácora.
 */
final class ProgramarTurnoDiagnostico
{
    public function __construct(
        private readonly DiagnosticoAtencionContract $diagnosticos,
        private readonly AgendaAccessScope $access,
        private readonly ProgramacionGuard $guard,
        private readonly PatientDirectoryContract $pacientes,
        private readonly CatalogoServiciosDirectoryContract $catalogo,
        private readonly ProfessionalScheduleContract $schedule,
        private readonly DiagnosticoParametros $parametros,
        private readonly DiagnosticoClock $clock,
        private readonly DuracionTurnoRule $duracionRule,
        private readonly DiasHabilitadosRule $dias,
        private readonly HorarioAgendaRule $horario,
        private readonly IntervaloMinimoRule $intervalo,
        private readonly TurnoSolapamientoRule $solapamiento,
        private readonly TurnosExistentesResolver $turnosExistentes,
        private readonly TurnoLock $lock,
        private readonly DiagnosticoEstadoSincronizador $sincronizador,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        int $diagnosticoId,
        int $profesionalId,
        string $fecha,
        string $horaInicio,
        ?int $duracionMinutos,
        User $actor,
        ?string $ip = null,
    ): array {
        return DB::transaction(function () use (
            $diagnosticoId,
            $profesionalId,
            $fecha,
            $horaInicio,
            $duracionMinutos,
            $actor,
            $ip,
        ): array {
            $resumen = $this->diagnosticos->resumenParaActualizar($diagnosticoId);

            if ($resumen === null || ! in_array($resumen->sucursalId, $this->access->accesibles($actor), true)) {
                throw new DiagnosticoNotFoundException($diagnosticoId);
            }

            $this->assertCoordinable($resumen);
            $this->assertProfesional($resumen, $profesionalId);
            $this->guard->asegurarProfesional($profesionalId, $resumen->sucursalId);
            $this->pacientes->asegurarPermiteTurno($resumen->pacienteId);

            if ($fecha < $resumen->fechaInicio || $fecha > $resumen->fechaFin) {
                throw new DiagnosticoFueraDePeriodoException(
                    $fecha,
                    $resumen->fechaInicio,
                    $resumen->fechaFin,
                );
            }

            $duracion = $this->duracion($resumen, $duracionMinutos);
            $inicio = $this->clock->fechaHora($fecha, $horaInicio);
            $fin = $inicio->addMinutes($duracion);
            $horaFin = $fin->format('H:i');

            $this->lock->porPacienteYProfesional($resumen->pacienteId, $profesionalId, $fecha);
            $this->assertDisponible($resumen, $profesionalId, $fecha, $horaInicio, $horaFin, $inicio, $fin);
            $this->assertSinTurnoVigente($diagnosticoId, $profesionalId);

            try {
                $turno = Turno::query()->create([
                    'sucursal_id' => $resumen->sucursalId,
                    'paciente_id' => $resumen->pacienteId,
                    'sala_id' => null,
                    'paquete_sesiones_id' => null,
                    'atencion_diagnostico_id' => $diagnosticoId,
                    'servicio_id' => $resumen->servicioId,
                    'profesional_id' => $profesionalId,
                    'fecha' => $fecha,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                    'duracion_minutos' => $duracion,
                    'estado' => EstadoTurno::PROGRAMADO->value,
                    'usuario_registro_id' => $actor->id,
                ]);
            } catch (QueryException $exception) {
                if ($this->lock->esViolacionDeDisponibilidad($exception)) {
                    throw new DisponibilidadDiagnosticoException([
                        'El paciente ya tiene un turno en ese horario.',
                    ]);
                }

                throw $exception;
            }

            $estado = $this->sincronizador->sincronizar($diagnosticoId, $actor, $ip);

            event(new TurnoDiagnosticoProgramado(
                turnoId: (int) $turno->id,
                diagnosticoId: $diagnosticoId,
                sucursalId: $resumen->sucursalId,
                pacienteId: $resumen->pacienteId,
                profesionalId: $profesionalId,
                fecha: $fecha,
                horaInicio: $horaInicio,
                horaFin: $horaFin,
                duracionMinutos: $duracion,
                actorId: (int) $actor->id,
                ip: $ip,
            ));

            return [
                'turno' => [
                    'id' => (int) $turno->id,
                    'diagnostico_id' => $diagnosticoId,
                    'profesional_id' => $profesionalId,
                    'fecha' => $fecha,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                    'duracion_minutos' => $duracion,
                    'estado' => EstadoTurno::PROGRAMADO->value,
                    'estado_label' => EstadoTurno::PROGRAMADO->label(),
                ],
                'estado_general' => $estado?->value ?? $resumen->estadoGeneral,
                'estado_general_label' => ($estado ?? EstadoGeneralDiagnostico::INCOMPLETO)->label(),
            ];
        });
    }

    private function assertCoordinable(DiagnosticoAtencionResumen $resumen): void
    {
        if ($resumen->estado !== 'activa'
            || $resumen->estadoGeneral === EstadoGeneralDiagnostico::CANCELADO->value) {
            throw new DiagnosticoNoCoordinableException($resumen->estado, $resumen->estadoGeneral);
        }
    }

    private function assertProfesional(DiagnosticoAtencionResumen $resumen, int $profesionalId): void
    {
        $profesional = null;

        foreach ($resumen->profesionales as $item) {
            if ((int) $item['id'] === $profesionalId) {
                $profesional = $item;
                break;
            }
        }

        if ($profesional === null) {
            throw new ProfesionalNoAsignadoAlDiagnosticoException($profesionalId);
        }

        if ($resumen->requisitos !== []) {
            $requeridas = array_map(
                static fn (array $requisito): int => (int) $requisito['especialidad_id'],
                $resumen->requisitos,
            );
            $cubiertas = array_map(
                static fn (array $especialidad): int => (int) $especialidad['id'],
                $profesional['especialidades'],
            );

            if (array_intersect($requeridas, $cubiertas) === []) {
                throw new ProfesionalNoCubreRequisitoException($profesionalId);
            }
        }
    }

    private function duracion(DiagnosticoAtencionResumen $resumen, ?int $solicitada): int
    {
        if ($solicitada !== null) {
            if (! $this->duracionRule->esValida($solicitada)) {
                throw new DisponibilidadDiagnosticoException(['La duración del turno debe ser mayor a cero.']);
            }

            return $solicitada;
        }

        $servicio = $this->catalogo->servicio($resumen->servicioId);

        return $this->duracionRule->resolver(
            $servicio?->duracionMinutos,
            $this->parametros->duracionSesionMinutos(),
        ) ?? $this->parametros->duracionSesionMinutos();
    }

    private function assertDisponible(
        DiagnosticoAtencionResumen $resumen,
        int $profesionalId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        CarbonImmutable $inicio,
        CarbonImmutable $fin,
    ): void {
        $motivos = [
            ...$this->dias->errores($fecha, $this->parametros->diasHabilitados()),
            ...$this->horario->errores(
                $horaInicio,
                $horaFin,
                $this->parametros->apertura(),
                $this->parametros->cierre(),
            ),
        ];

        if ($this->schedule->haySolapamiento(
            $profesionalId,
            $inicio->setTimezone('UTC'),
            $fin->setTimezone('UTC'),
        )) {
            $motivos[] = 'El profesional ya tiene un turno en ese horario.';
        }

        $candidato = new TurnoCandidato(
            pacienteId: $resumen->pacienteId,
            profesionalId: $profesionalId,
            salaId: null,
            servicioId: $resumen->servicioId,
            fecha: $fecha,
            horaInicio: $horaInicio,
            horaFin: $horaFin,
        );

        $turnosPaciente = Turno::query()
            ->where('paciente_id', $resumen->pacienteId)
            ->where('fecha', $fecha)
            ->vigentes()
            ->get()
            ->map(static fn (Turno $turno): TurnoExistente => TurnoExistente::fromTurno($turno))
            ->all();

        foreach ($this->solapamiento->evaluar($candidato, $turnosPaciente) as $hallazgo) {
            if ($hallazgo->tipo === 'paciente') {
                $motivos[] = $hallazgo->mensaje;
            }
        }

        $motivos = [
            ...$motivos,
            ...$this->intervalo->errores(
                $profesionalId,
                $fecha,
                $horaInicio,
                $horaFin,
                $this->turnosExistentes->delProfesional($profesionalId, $fecha, $fecha),
                $this->parametros->intervaloMinimo(),
            ),
        ];

        $motivos = array_values(array_unique($motivos));

        if ($motivos !== []) {
            throw new DisponibilidadDiagnosticoException($motivos);
        }
    }

    private function assertSinTurnoVigente(int $diagnosticoId, int $profesionalId): void
    {
        $existente = Turno::query()
            ->where('atencion_diagnostico_id', $diagnosticoId)
            ->where('profesional_id', $profesionalId)
            ->whereIn('estado', [EstadoTurno::PROGRAMADO->value, EstadoTurno::ASISTIO->value])
            ->lockForUpdate()
            ->first();

        if ($existente !== null) {
            throw new TurnoDiagnosticoDuplicadoException($profesionalId, (int) $existente->id);
        }
    }
}
