<?php

namespace App\Modules\AgendaTurnos\Actions\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Events\TurnoCancelado;
use App\Modules\AgendaTurnos\Events\TurnosCanceladosMasivamente;
use App\Modules\AgendaTurnos\Exceptions\ConjuntoCancelacionCambioException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\CancelacionMasivaCalculator;
use App\Modules\AgendaTurnos\Support\CancelaTurno;
use App\Modules\AgendaTurnos\Support\DiagnosticoEstadoSincronizador;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * [Reprogramación y Cancelación] RF-011 / RN-009 / CA-007 / CU-03.
 *
 * Una transacción, un solo motivo, conjunto exacto de ids, todas las
 * sucursales accesibles y cancelación de cada turno con historial/bitácora.
 */
final class CancelarTurnosMasivo
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly CancelacionMasivaCalculator $calculator,
        private readonly CancelaTurno $cancelacion,
        private readonly DiagnosticoEstadoSincronizador $diagnosticos,
    ) {}

    /**
     * @param  list<array{tipo: string, id: int}>  $entidades
     * @param  list<int>  $turnoIds
     * @return array<string, mixed>
     */
    public function __invoke(
        int $pacienteId,
        array $entidades,
        ?int $servicioId,
        int $motivoId,
        ?string $motivoTexto,
        array $turnoIds,
        User $actor,
        ?string $ip = null,
    ): array {
        return DB::transaction(function () use (
            $pacienteId,
            $entidades,
            $servicioId,
            $motivoId,
            $motivoTexto,
            $turnoIds,
            $actor,
            $ip,
        ): array {
            $preview = $this->calculator->calcular(
                $pacienteId,
                $entidades,
                $servicioId,
                $motivoId,
                $motivoTexto,
                $actor,
            );

            $solicitados = $this->ids($turnoIds);
            $elegibles = $this->ids($preview['clasificacion']->idsElegibles());

            if ($solicitados !== $elegibles) {
                throw new ConjuntoCancelacionCambioException($this->preview($preview));
            }

            $turnos = Turno::query()
                ->whereIn('id', $solicitados)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($turnos->count() !== count($solicitados)) {
                throw new ConjuntoCancelacionCambioException($this->preview($preview));
            }

            foreach ($turnos as $turno) {
                $this->access->assertSucursal($actor, (int) $turno->sucursal_id);
            }

            $revalidado = $this->calculator->calcular(
                $pacienteId,
                $entidades,
                $servicioId,
                $motivoId,
                $motivoTexto,
                $actor,
            );

            if ($this->ids($revalidado['clasificacion']->idsElegibles()) !== $solicitados) {
                throw new ConjuntoCancelacionCambioException($this->preview($revalidado));
            }

            $motivo = $revalidado['motivo'];
            $consume = $motivo->descuentaSaldo === true;
            $loteId = (string) Str::uuid();
            $ids = [];
            $diagnosticos = [];

            foreach ($turnos as $turno) {
                $this->cancelacion->aplicar(
                    $turno,
                    $motivo,
                    $motivoTexto,
                    $consume,
                    $actor,
                    $loteId,
                    true,
                );

                $ids[] = (int) $turno->id;

                if ($turno->atencion_diagnostico_id !== null) {
                    $diagnosticos[(int) $turno->atencion_diagnostico_id] = true;
                }

                event(new TurnoCancelado(
                    turnoId: (int) $turno->id,
                    sucursalId: (int) $turno->sucursal_id,
                    pacienteId: (int) $turno->paciente_id,
                    fecha: $turno->fecha->toDateString(),
                    horaInicio: (string) $turno->hora_inicio,
                    horaFin: (string) $turno->hora_fin,
                    motivo: $motivo,
                    consumeSaldo: $consume,
                    advertencias: [],
                    actorId: (int) $actor->id,
                    ip: $ip,
                    masivo: true,
                    loteId: $loteId,
                    motivoTexto: $motivoTexto,
                ));
            }

            foreach (array_keys($diagnosticos) as $diagnosticoId) {
                $this->diagnosticos->sincronizar($diagnosticoId, $actor, $ip);
            }

            event(new TurnosCanceladosMasivamente(
                loteId: $loteId,
                pacienteId: $pacienteId,
                entidades: $entidades,
                turnoIds: $ids,
                sucursales: $revalidado['sucursales'],
                motivo: $motivo,
                actorId: (int) $actor->id,
                ip: $ip,
                motivoTexto: $motivoTexto,
            ));

            return [
                'lote_id' => $loteId,
                'cantidad' => count($ids),
                'turno_ids' => $ids,
                'entidades' => $entidades,
                'sucursales' => $revalidado['sucursales'],
                'motivo' => [
                    'id' => $motivo->id,
                    'codigo' => $motivo->codigo,
                    'nombre' => $motivo->nombre,
                    'origen' => $motivo->origen?->value,
                    'descuenta_saldo' => $motivo->descuentaSaldo,
                ],
                'consume_saldo' => $consume,
                'excluidos' => $revalidado['excluidos'],
            ];
        });
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        $ids = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            $ids,
        )));
        sort($ids);

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $preview
     * @return array<string, mixed>
     */
    private function preview(array $preview): array
    {
        return [
            'elegibles' => $preview['elegibles'],
            'excluidos' => $preview['excluidos'],
            'cantidades' => $preview['cantidades'],
            'entidades' => $preview['entidades'],
        ];
    }
}
