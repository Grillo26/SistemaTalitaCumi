<?php

namespace App\Modules\AgendaTurnos\Actions\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Events\TurnoReprogramado;
use App\Modules\AgendaTurnos\Exceptions\AdvertenciasCambioNoConfirmadasException;
use App\Modules\AgendaTurnos\Exceptions\ConflictoDisponibilidadException;
use App\Modules\AgendaTurnos\Exceptions\TurnoNotFoundException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\Hallazgo;
use App\Modules\AgendaTurnos\Support\ReprogramacionCalculada;
use App\Modules\AgendaTurnos\Support\ReprogramacionCalculator;
use App\Modules\AgendaTurnos\Support\ReprogramacionInput;
use App\Modules\AgendaTurnos\Support\TurnoLock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * [Reprogramación y Cancelación] RF-001 / RF-004 / RF-005 / RF-006 / RF-007 /
 * RF-013 / RF-014 / RN-001 / RN-002 / RN-005 / RT-001 / CA-001 / CA-004.
 *
 * Una transacción: lock del original, estado esperado, motivo, nueva fecha,
 * disponibilidad, original a Reprogramado, nuevo turno vinculado e historial.
 */
final class ReprogramarTurno
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly TurnoLock $lock,
        private readonly ReprogramacionCalculator $calculator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        int $turnoId,
        ReprogramacionInput $input,
        User $actor,
        ?string $ip = null,
    ): array {
        return DB::transaction(function () use ($turnoId, $input, $actor, $ip): array {
            $turno = Turno::query()->whereKey($turnoId)->lockForUpdate()->first();

            if ($turno === null) {
                throw new TurnoNotFoundException($turnoId);
            }

            $this->access->assertSucursal($actor, (int) $turno->sucursal_id);

            $profesionalNuevo = $input->profesionalProporcionado
                ? $input->profesionalId
                : ($turno->profesional_id === null ? null : (int) $turno->profesional_id);

            $this->lock->porPacienteYProfesional(
                (int) $turno->paciente_id,
                $profesionalNuevo,
                $input->fecha,
            );

            $calculada = $this->calculator->calcular($turno, $input, $actor);

            $bloqueos = $this->hallazgosPorSeveridad($calculada->hallazgos, 'bloqueo');

            if ($bloqueos !== []) {
                throw new ConflictoDisponibilidadException($bloqueos);
            }

            $advertencias = $this->hallazgosPorSeveridad($calculada->hallazgos, 'advertencia');

            if ($advertencias !== [] && ! $input->confirmarAdvertencias) {
                throw new AdvertenciasCambioNoConfirmadasException($advertencias);
            }

            $turno->forceFill([
                'estado' => 'reprogramado',
                'motivo_turno_id' => $calculada->motivo->id,
                'motivo_detalle' => $this->texto($input->motivoTexto),
            ])->save();

            try {
                $nuevo = Turno::query()->create([
                    'sucursal_id' => $turno->sucursal_id,
                    'paciente_id' => $turno->paciente_id,
                    'sala_id' => $calculada->nuevo['sala_id'],
                    'inscripcion_sala_id' => $turno->inscripcion_sala_id,
                    'paquete_sesiones_id' => $turno->paquete_sesiones_id,
                    'atencion_diagnostico_id' => $turno->atencion_diagnostico_id,
                    'servicio_id' => $turno->servicio_id,
                    'profesional_id' => $calculada->nuevo['profesional_id'],
                    'fecha' => $calculada->nuevo['fecha'],
                    'hora_inicio' => $calculada->nuevo['hora_inicio'],
                    'hora_fin' => $calculada->nuevo['hora_fin'],
                    'duracion_minutos' => $calculada->nuevo['duracion_minutos'],
                    'estado' => 'programado',
                    'turno_origen_id' => $turno->id,
                    'usuario_registro_id' => $actor->id,
                ]);
            } catch (QueryException $exception) {
                if ($this->lock->esViolacionDeDisponibilidad($exception)) {
                    throw new ConflictoDisponibilidadException([
                        new Hallazgo(
                            tipo: 'disponibilidad',
                            severidad: SeveridadHallazgo::BLOQUEO,
                            mensaje: 'El horario fue tomado por otra operación. Elegí otro horario.',
                            fecha: $calculada->nuevo['fecha'],
                            horaInicio: $calculada->nuevo['hora_inicio'],
                        ),
                    ]);
                }

                throw $exception;
            }

            event(new TurnoReprogramado(
                turnoOriginalId: (int) $turno->id,
                turnoNuevoId: (int) $nuevo->id,
                sucursalId: (int) $turno->sucursal_id,
                pacienteId: (int) $turno->paciente_id,
                motivo: $calculada->motivo,
                original: [
                    ...$calculada->original,
                    'motivo_texto' => $this->texto($input->motivoTexto),
                ],
                nuevo: [
                    ...$calculada->nuevo,
                    'id' => (int) $nuevo->id,
                    'estado' => 'programado',
                ],
                advertencias: $calculada->advertencias(),
                actorId: (int) $actor->id,
                ip: $ip,
            ));

            return [
                'turno_original' => [
                    ...$calculada->original,
                    'estado' => 'reprogramado',
                    'estado_label' => 'Reprogramado',
                ],
                'turno_nuevo' => [
                    ...$calculada->nuevo,
                    'id' => (int) $nuevo->id,
                    'estado' => 'programado',
                    'estado_label' => 'Programado',
                ],
                'motivo' => $this->motivoArray($calculada),
                'advertencias' => $calculada->advertencias(),
            ];
        });
    }

    /**
     * @param  list<Hallazgo>  $hallazgos
     * @return list<Hallazgo>
     */
    private function hallazgosPorSeveridad(array $hallazgos, string $severidad): array
    {
        return array_values(array_filter(
            $hallazgos,
            static fn (Hallazgo $hallazgo): bool => $hallazgo->severidad->value === $severidad,
        ));
    }

    private function texto(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : $texto;
    }

    /**
     * @return array<string, mixed>
     */
    private function motivoArray(ReprogramacionCalculada $calculada): array
    {
        return [
            'id' => $calculada->motivo->id,
            'codigo' => $calculada->motivo->codigo,
            'nombre' => $calculada->motivo->nombre,
            'origen' => $calculada->motivo->origen?->value,
            'descuenta_saldo' => $calculada->motivo->descuentaSaldo,
        ];
    }
}
