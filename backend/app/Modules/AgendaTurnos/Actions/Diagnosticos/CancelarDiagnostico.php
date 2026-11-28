<?php

namespace App\Modules\AgendaTurnos\Actions\Diagnosticos;

use App\Models\User;
use App\Modules\AgendaTurnos\Actions\Cambios\CancelarTurno;
use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\DiagnosticoCancelado;
use App\Modules\AgendaTurnos\Exceptions\ConfiguracionDiagnosticoNoDisponibleException;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoNoCoordinableException;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoNotFoundException;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoYaCanceladoException;
use App\Modules\AgendaTurnos\Models\MotivoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\RegeneracionRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\AgendaTurnos\Support\DiagnosticosModule;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionContract;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionResumen;
use Illuminate\Support\Facades\DB;

/**
 * 07.05/RF-007 / 07.05/RF-009 / 07.05/CU-02 FA-01 / 07.05/CA-007.
 *
 * Cancela el diagnóstico completo con motivo obligatorio. Los turnos futuros
 * `programado` se cancelan reutilizando la Action de 07.3; los turnos pasados
 * (asistió/faltó) y los ya cancelados/reprogramados se conservan. El estado
 * general se persiste como `cancelado` en la misma transacción.
 */
final class CancelarDiagnostico
{
    public function __construct(
        private readonly DiagnosticoAtencionContract $diagnosticos,
        private readonly AgendaAccessScope $access,
        private readonly CancelarTurno $cancelarTurno,
        private readonly AgendaClock $clock,
        private readonly RegeneracionRule $futuro,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        int $diagnosticoId,
        string $motivo,
        User $actor,
        ?string $ip = null,
    ): array {
        return DB::transaction(function () use ($diagnosticoId, $motivo, $actor, $ip): array {
            $resumen = $this->diagnosticos->resumenParaActualizar($diagnosticoId);

            if ($resumen === null || ! in_array($resumen->sucursalId, $this->access->accesibles($actor), true)) {
                throw new DiagnosticoNotFoundException($diagnosticoId);
            }

            $this->assertCoordinable($resumen);

            $motivoTurno = MotivoTurno::query()
                ->where('codigo', DiagnosticosModule::MOTIVO_CANCELACION)
                ->first();

            if ($motivoTurno === null) {
                throw new ConfiguracionDiagnosticoNoDisponibleException(
                    'motivo_turno.'.DiagnosticosModule::MOTIVO_CANCELACION,
                );
            }

            $turnos = Turno::query()
                ->where('atencion_diagnostico_id', $diagnosticoId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $hoy = $this->clock->hoy();
            $horaActual = $this->clock->horaActual();
            $cancelados = [];
            $conservados = 0;

            foreach ($turnos as $turno) {
                $existente = TurnoExistente::fromTurno($turno);

                if ($turno->estado !== EstadoTurno::PROGRAMADO
                    || ! $this->futuro->esFuturo($existente, $hoy, $horaActual)) {
                    $conservados++;

                    continue;
                }

                ($this->cancelarTurno)(
                    (int) $turno->id,
                    (int) $motivoTurno->id,
                    $motivo,
                    EstadoTurno::PROGRAMADO,
                    $actor,
                    $ip,
                );

                $cancelados[] = (int) $turno->id;
            }

            $this->diagnosticos->actualizarEstadoGeneral(
                $diagnosticoId,
                EstadoGeneralDiagnostico::CANCELADO->value,
            );

            event(new DiagnosticoCancelado(
                diagnosticoId: $diagnosticoId,
                sucursalId: $resumen->sucursalId,
                pacienteId: $resumen->pacienteId,
                estadoAnterior: EstadoGeneralDiagnostico::tryFrom($resumen->estadoGeneral)
                    ?? EstadoGeneralDiagnostico::INCOMPLETO,
                motivo: $motivo,
                turnosCancelados: $cancelados,
                turnosConservados: $conservados,
                actorId: (int) $actor->id,
                ip: $ip,
            ));

            return [
                'diagnostico_id' => $diagnosticoId,
                'estado_general' => EstadoGeneralDiagnostico::CANCELADO->value,
                'estado_general_label' => EstadoGeneralDiagnostico::CANCELADO->label(),
                'turnos_cancelados' => $cancelados,
                'cantidad_cancelados' => count($cancelados),
                'turnos_conservados' => $conservados,
            ];
        });
    }

    private function assertCoordinable(DiagnosticoAtencionResumen $resumen): void
    {
        if ($resumen->estadoGeneral === EstadoGeneralDiagnostico::CANCELADO->value) {
            throw new DiagnosticoYaCanceladoException;
        }

        if ($resumen->estado !== 'activa') {
            throw new DiagnosticoNoCoordinableException($resumen->estado, $resumen->estadoGeneral);
        }
    }
}
