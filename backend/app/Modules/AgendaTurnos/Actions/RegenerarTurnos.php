<?php

namespace App\Modules\AgendaTurnos\Actions;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\AccionLoteGeneracion;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Events\TurnoCanceladoPorRegeneracion;
use App\Modules\AgendaTurnos\Events\TurnosRegenerados;
use App\Modules\AgendaTurnos\Exceptions\OrigenNoGenerableException;
use App\Modules\AgendaTurnos\Exceptions\PatronNoDisponibleException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Models\TurnoLoteGeneracion;
use App\Modules\AgendaTurnos\Rules\CantidadRestanteRule;
use App\Modules\AgendaTurnos\Rules\OrigenGenerableRule;
use App\Modules\AgendaTurnos\Rules\RegeneracionRule;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\AgendaTurnos\Support\EntradaGeneracion;
use App\Modules\AgendaTurnos\Support\FeriadosResolver;
use App\Modules\AgendaTurnos\Support\GeneradorTurnos;
use App\Modules\AgendaTurnos\Support\LoteIdempotencia;
use App\Modules\AgendaTurnos\Support\LoteResultado;
use App\Modules\AgendaTurnos\Support\OrigenProgramacionResolver;
use App\Modules\AgendaTurnos\Support\ProgramacionGuard;
use App\Modules\AgendaTurnos\Support\TurnoBatchWriter;
use App\Modules\AgendaTurnos\Support\TurnoFilasFactory;
use App\Modules\AgendaTurnos\Support\TurnosExistentesResolver;
use Illuminate\Support\Facades\DB;

/**
 * [Programación de Turnos] RF-010 / RF-011 / RF-012 / RN-006 / RN-007 /
 * RT-004 / CA-007 / CA-008.
 *
 * Regeneración transaccional: cancela solo futuros Programados, preserva
 * Asistió/Faltó/Cancelado/Reprogramado y los Programados pasados, y crea los
 * turnos nuevos.
 */
final class RegenerarTurnos
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
        private readonly RegeneracionRule $regeneracion,
        private readonly CantidadRestanteRule $cantidadRestante,
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
                    AccionLoteGeneracion::REGENERAR,
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

            $hoy = $this->clock->hoy();
            $horaActual = $this->clock->horaActual();
            $existente = $this->turnos->delOrigen($tipo, $origenId);
            $clasificacion = $this->regeneracion->evaluar($existente, $hoy, $horaActual);

            $patron = $origen->patron;

            if ($patron === null) {
                throw new PatronNoDisponibleException;
            }

            $this->guard->asegurarProfesional($patron->profesionalId, $origen->sucursalId);

            $objetivo = $origen->esPaquete() && $origen->cantidadSesiones !== null
                ? $this->cantidadRestante->calcular($origen->cantidadSesiones, $existente)
                : null;

            $desde = max($origen->fechaInicio, $hoy);
            $hasta = $origen->fechaFin;
            $conflictivos = $this->turnos->conflictivos(
                $origen,
                $patron->profesionalId,
                $desde,
                $hasta,
            );
            $feriados = $this->feriados->mapa($origen->sucursalId, $desde, $hasta);

            $calculo = $this->calculador->calcular(
                origen: $origen,
                hoy: $hoy,
                horaActual: $horaActual,
                existentes: $conflictivos,
                feriados: $feriados,
                confirmarAdvertencias: $entrada->confirmarAdvertencias,
                excluirTurnoIds: $clasificacion->idsCancelar(),
                cantidadObjetivo: $objetivo,
            );

            if ($objetivo !== 0) {
                $this->guard->asegurarConfirmable($calculo);
            }

            $advertencias = $this->guard->advertencias($calculo);
            $cancelarIds = $clasificacion->idsCancelar();

            $lote = TurnoLoteGeneracion::query()->create([
                'tipo_origen' => $tipo->value,
                'origen_id' => $origen->id,
                'sucursal_id' => $origen->sucursalId,
                'accion' => AccionLoteGeneracion::REGENERAR->value,
                'clave_idempotencia' => $entrada->claveIdempotencia,
                'usuario_id' => $actor->id,
                'ip' => $entrada->ip,
                'cantidad_creados' => 0,
                'cantidad_cancelados' => count($cancelarIds),
                'cantidad_preservados' => $clasificacion->cantidadPreservar(),
                'advertencias_confirmadas' => $advertencias,
            ]);

            if ($cancelarIds !== []) {
                $ahora = $this->clock->ahora();

                Turno::query()
                    ->whereIn('id', $cancelarIds)
                    ->update([
                        'estado' => 'cancelado',
                        'cancelado_por_lote_id' => (int) $lote->id,
                        'cancelacion_origen' => 'regeneracion',
                        'cancelado_en' => $ahora,
                        'motivo_cancelacion' => 'Regeneración de turnos',
                        'updated_at' => $ahora,
                    ]);

                event(new TurnoCanceladoPorRegeneracion(
                    loteId: (int) $lote->id,
                    tipoOrigen: $tipo,
                    origenId: $origen->id,
                    sucursalId: $origen->sucursalId,
                    turnoIds: $cancelarIds,
                    actorId: (int) $actor->id,
                    ip: $entrada->ip,
                ));
            }

            $this->writer->insertar(
                $this->filas->desdeCalculo($calculo, $origen, $patron, $lote, $actor),
            );

            $creadosIds = $lote->idsCreados();

            $lote->forceFill(['cantidad_creados' => count($creadosIds)])->save();

            event(new TurnosRegenerados(
                loteId: (int) $lote->id,
                tipoOrigen: $tipo,
                origenId: $origen->id,
                sucursalId: $origen->sucursalId,
                cantidadCreados: count($creadosIds),
                cantidadCancelados: count($cancelarIds),
                cantidadPreservados: $clasificacion->cantidadPreservar(),
                turnosCreadosIds: $creadosIds,
                turnosCanceladosIds: $cancelarIds,
                advertencias: $advertencias,
                actorId: (int) $actor->id,
                ip: $entrada->ip,
            ));

            return new LoteResultado(
                loteId: (int) $lote->id,
                tipoOrigen: $tipo,
                origenId: $origen->id,
                accion: AccionLoteGeneracion::REGENERAR,
                cantidadCreados: count($creadosIds),
                cantidadCancelados: count($cancelarIds),
                cantidadPreservados: $clasificacion->cantidadPreservar(),
                turnoIds: $creadosIds,
            );
        });
    }
}
