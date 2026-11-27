<?php

namespace App\Modules\AgendaTurnos\Actions;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\AccionLoteGeneracion;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Events\TurnosGenerados;
use App\Modules\AgendaTurnos\Exceptions\OrigenNoGenerableException;
use App\Modules\AgendaTurnos\Exceptions\PatronNoDisponibleException;
use App\Modules\AgendaTurnos\Exceptions\TurnosYaGeneradosException;
use App\Modules\AgendaTurnos\Models\TurnoLoteGeneracion;
use App\Modules\AgendaTurnos\Rules\OrigenGenerableRule;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\AgendaTurnos\Support\EntradaGeneracion;
use App\Modules\AgendaTurnos\Support\FeriadosResolver;
use App\Modules\AgendaTurnos\Support\GeneradorTurnos;
use App\Modules\AgendaTurnos\Support\LoteIdempotencia;
use App\Modules\AgendaTurnos\Support\LoteResultado;
use App\Modules\AgendaTurnos\Support\OrigenProgramacion;
use App\Modules\AgendaTurnos\Support\OrigenProgramacionResolver;
use App\Modules\AgendaTurnos\Support\PatronProgramacion;
use App\Modules\AgendaTurnos\Support\ProgramacionGuard;
use App\Modules\AgendaTurnos\Support\TurnoBatchWriter;
use App\Modules\AgendaTurnos\Support\TurnoFilasFactory;
use App\Modules\AgendaTurnos\Support\TurnosExistentesResolver;
use Illuminate\Support\Facades\DB;

/**
 * [Programación de Turnos] RF-002 / RF-003 / RF-004 / RF-005 / RF-006 /
 * RF-008 / RF-009 / RF-012 / RF-013 / RN-007 / RT-001 / CA-001 / CA-008.
 *
 * Generación manual desde una entidad origen. Todo ocurre en una única
 * transacción: lote, turnos y bitácora (vía evento) o nada.
 */
final class GenerarTurnosDesdeOrigen
{
    public function __construct(
        private readonly OrigenProgramacionResolver $origenes,
        private readonly GeneradorTurnos $calculador,
        private readonly TurnosExistentesResolver $turnos,
        private readonly FeriadosResolver $feriados,
        private readonly TurnoBatchWriter $writer,
        private readonly TurnoFilasFactory $filas,
        private readonly LoteIdempotencia $idempotencia,
        private readonly AgendaClock $clock,
        private readonly OrigenGenerableRule $generable,
        private readonly ProgramacionGuard $guard,
    ) {}

    public function __invoke(
        TipoOrigenTurno $tipo,
        int $origenId,
        EntradaGeneracion $entrada,
        User $actor,
    ): LoteResultado {
        return DB::transaction(function () use ($tipo, $origenId, $entrada, $actor): LoteResultado {
            $lotePrevio = $this->idempotencia->buscar($entrada->claveIdempotencia);

            if ($lotePrevio !== null) {
                $this->idempotencia->asegurarCoincide(
                    $lotePrevio,
                    $tipo,
                    $origenId,
                    AccionLoteGeneracion::GENERAR,
                );

                return $lotePrevio->resultado();
            }

            $this->guard->bloquearOrigen($tipo, $origenId);

            $origen = $this->origenes->resolver($tipo, $origenId);
            $this->guard->asegurarAcceso($actor, $origen->sucursalId);

            if (! $this->generable->permite($origen->estadoEfectivo)) {
                throw new OrigenNoGenerableException($origen->estadoEfectivo, $tipo);
            }

            if (! $origen->patronDisponible()) {
                throw new PatronNoDisponibleException(
                    $origen->patronNoDisponibleMotivo
                        ?? 'La entidad origen no tiene un patrón de días y hora configurado.',
                );
            }

            $activos = $this->turnos->activosDelOrigen($tipo, $origenId);

            if ($activos > 0) {
                throw new TurnosYaGeneradosException($activos);
            }

            $patron = $this->patronEfectivo($origen, $entrada);
            $this->guard->asegurarProfesional($patron->profesionalId, $origen->sucursalId);

            $hoy = $this->clock->hoy();
            $horaActual = $this->clock->horaActual();
            $desde = max($origen->fechaInicio, $hoy);
            $hasta = $origen->fechaFin;

            $existentes = $this->turnos->conflictivos(
                $origen,
                $patron->profesionalId,
                $desde,
                $hasta,
            );
            $feriados = $this->feriados->mapa($origen->sucursalId, $desde, $hasta);

            $calculo = $this->calculador->calcular(
                origen: $origen->conPatron($patron),
                hoy: $hoy,
                horaActual: $horaActual,
                existentes: $existentes,
                feriados: $feriados,
                confirmarAdvertencias: $entrada->confirmarAdvertencias,
            );

            $this->guard->asegurarConfirmable($calculo);

            $advertencias = $this->guard->advertencias($calculo);

            $lote = TurnoLoteGeneracion::query()->create([
                'tipo_origen' => $tipo->value,
                'origen_id' => $origen->id,
                'sucursal_id' => $origen->sucursalId,
                'accion' => AccionLoteGeneracion::GENERAR->value,
                'clave_idempotencia' => $entrada->claveIdempotencia,
                'usuario_id' => $actor->id,
                'ip' => $entrada->ip,
                'cantidad_creados' => 0,
                'cantidad_cancelados' => 0,
                'cantidad_preservados' => 0,
                'advertencias_confirmadas' => $advertencias,
            ]);

            $this->writer->insertar(
                $this->filas->desdeCalculo($calculo, $origen, $patron, $lote, $actor),
            );
            $ids = $lote->idsCreados();

            $lote->forceFill(['cantidad_creados' => count($ids)])->save();

            event(new TurnosGenerados(
                loteId: (int) $lote->id,
                tipoOrigen: $tipo,
                origenId: $origen->id,
                sucursalId: $origen->sucursalId,
                cantidadCreados: count($ids),
                turnoIds: $ids,
                advertencias: $advertencias,
                actorId: (int) $actor->id,
                ip: $entrada->ip,
            ));

            return new LoteResultado(
                loteId: (int) $lote->id,
                tipoOrigen: $tipo,
                origenId: $origen->id,
                accion: AccionLoteGeneracion::GENERAR,
                cantidadCreados: count($ids),
                cantidadCancelados: 0,
                cantidadPreservados: 0,
                turnoIds: $ids,
            );
        });
    }

    private function patronEfectivo(
        OrigenProgramacion $origen,
        EntradaGeneracion $entrada,
    ): PatronProgramacion {
        $patron = $origen->patron;

        if ($patron === null) {
            throw new PatronNoDisponibleException;
        }

        if ($entrada->usarProfesionalOrigen) {
            return $patron;
        }

        return new PatronProgramacion(
            diasSemana: $patron->diasSemana,
            horaInicio: $patron->horaInicio,
            duracionMinutos: $patron->duracionMinutos,
            profesionalId: $entrada->profesionalId,
        );
    }
}
