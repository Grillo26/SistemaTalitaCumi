<?php

namespace App\Modules\AgendaTurnos\Queries;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Exceptions\OrigenNoGenerableException;
use App\Modules\AgendaTurnos\Exceptions\PatronNoDisponibleException;
use App\Modules\AgendaTurnos\Rules\CantidadRestanteRule;
use App\Modules\AgendaTurnos\Rules\OrigenGenerableRule;
use App\Modules\AgendaTurnos\Rules\RegeneracionRule;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\AgendaTurnos\Support\ClasificacionRegeneracion;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\AgendaTurnos\Support\FeriadosResolver;
use App\Modules\AgendaTurnos\Support\GeneradorTurnos;
use App\Modules\AgendaTurnos\Support\Hallazgo;
use App\Modules\AgendaTurnos\Support\OrigenProgramacionResolver;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use App\Modules\AgendaTurnos\Support\TurnosExistentesResolver;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;

/**
 * [Programación de Turnos] RF-010 / RF-011 / RF-014 / RN-006 / RT-004 /
 * CA-007. CU-02 paso 2.
 *
 * Previsualización de la regeneración: qué se cancela, qué se preserva y qué
 * se crearía. No persiste nada.
 */
final class PreviewRegeneracion
{
    public function __construct(
        private readonly OrigenProgramacionResolver $origenes,
        private readonly GeneradorTurnos $calculador,
        private readonly TurnosExistentesResolver $turnos,
        private readonly FeriadosResolver $feriados,
        private readonly DirectoriosAgenda $nombres,
        private readonly AgendaClock $clock,
        private readonly OrigenGenerableRule $generable,
        private readonly RegeneracionRule $regeneracion,
        private readonly CantidadRestanteRule $cantidadRestante,
        private readonly UserBranchAccessContract $branchAccess,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(TipoOrigenTurno $tipo, int $origenId, User $actor): array
    {
        $origen = $this->origenes->resolver($tipo, $origenId);

        if (! $this->branchAccess->puedeAcceder((int) $actor->id, $origen->sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        if (! $this->generable->permite($origen->estadoEfectivo)) {
            throw new OrigenNoGenerableException($origen->estadoEfectivo, $tipo);
        }

        if (! $origen->patronDisponible() || $origen->patron === null) {
            throw new PatronNoDisponibleException(
                $origen->patronNoDisponibleMotivo
                    ?? 'La entidad origen no tiene un patrón de días y hora configurado.',
            );
        }

        $hoy = $this->clock->hoy();
        $horaActual = $this->clock->horaActual();
        $existentes = $this->turnos->delOrigen($tipo, $origenId);
        $clasificacion = $this->regeneracion->evaluar($existentes, $hoy, $horaActual);
        $patron = $origen->patron;

        $objetivo = $origen->esPaquete() && $origen->cantidadSesiones !== null
            ? $this->cantidadRestante->calcular($origen->cantidadSesiones, $existentes)
            : null;

        $desde = max($origen->fechaInicio, $hoy);
        $hasta = $origen->fechaFin;

        $calculo = $this->calculador->calcular(
            origen: $origen,
            hoy: $hoy,
            horaActual: $horaActual,
            existentes: $this->turnos->conflictivos($origen, $patron->profesionalId, $desde, $hasta),
            feriados: $this->feriados->mapa($origen->sucursalId, $desde, $hasta),
            confirmarAdvertencias: false,
            excluirTurnoIds: $clasificacion->idsCancelar(),
            cantidadObjetivo: $objetivo,
        );

        $puedeConfirmar = $objetivo === 0
            ? $calculo->resumen->bloqueados === 0
            : $calculo->resumen->puedeConfirmar;

        $hallazgos = array_merge(
            $this->hallazgosPreservacion($clasificacion),
            $calculo->hallazgos,
        );

        return [
            'origen' => array_merge($origen->toArray(), [
                'paciente' => [
                    'id' => $origen->pacienteId,
                    'nombre' => $this->nombres->paciente($origen->pacienteId),
                ],
                'servicio' => [
                    'id' => $origen->servicioId,
                    'nombre' => $this->nombres->servicio($origen->servicioId),
                ],
                'sala' => $origen->salaId === null ? null : [
                    'id' => $origen->salaId,
                    'nombre' => $this->nombres->sala($origen->salaId),
                ],
                'profesional' => $patron->profesionalId === null ? null : [
                    'id' => $patron->profesionalId,
                    'nombre' => $this->nombres->profesional($patron->profesionalId),
                ],
            ]),
            'patron' => $patron->toArray(),
            'objetivo' => $objetivo,
            'cancelar' => array_map(
                fn (TurnoExistente $turno): array => $this->turnoResumen($turno),
                $clasificacion->cancelar,
            ),
            'preservar' => array_map(
                fn (TurnoExistente $turno): array => $this->turnoResumen($turno),
                $clasificacion->preservar,
            ),
            'filas' => $calculo->filasParaRespuesta(),
            'hallazgos' => array_map(
                static fn (Hallazgo $hallazgo): array => $hallazgo->toArray(),
                $hallazgos,
            ),
            'resumen' => array_merge($calculo->resumen->toArray(), [
                'puede_confirmar' => $puedeConfirmar,
                'cancelar' => $clasificacion->cantidadCancelar(),
                'preservar' => $clasificacion->cantidadPreservar(),
                'asistidos' => $clasificacion->cantidadAsistidos(),
                'faltados' => $clasificacion->cantidadFaltados(),
                'pasados_sin_marcar' => $clasificacion->cantidadPasadosSinMarcar(),
            ]),
        ];
    }

    /**
     * @return list<Hallazgo>
     */
    private function hallazgosPreservacion(ClasificacionRegeneracion $clasificacion): array
    {
        $hallazgos = [];

        if ($clasificacion->cantidadPasadosSinMarcar() > 0) {
            $hallazgos[] = new Hallazgo(
                tipo: 'programados_pasados',
                severidad: SeveridadHallazgo::INFORMATIVO,
                mensaje: "Se preservarán {$clasificacion->cantidadPasadosSinMarcar()} turnos programados de fechas pasadas sin marcar.",
            );
        }

        if ($clasificacion->cantidadAsistidos() > 0 || $clasificacion->cantidadFaltados() > 0) {
            $hallazgos[] = new Hallazgo(
                tipo: 'asistencia_preservada',
                severidad: SeveridadHallazgo::INFORMATIVO,
                mensaje: "Se preservarán {$clasificacion->cantidadAsistidos()} turnos asistidos y {$clasificacion->cantidadFaltados()} faltados.",
            );
        }

        if ($clasificacion->cantidadCancelar() === 0) {
            $hallazgos[] = new Hallazgo(
                tipo: 'sin_cancelaciones',
                severidad: SeveridadHallazgo::INFORMATIVO,
                mensaje: 'No hay turnos futuros programados para cancelar.',
            );
        }

        $reprogramados = count(array_filter(
            $clasificacion->cancelar,
            static fn (TurnoExistente $turno): bool => $turno->turnoOrigenId !== null,
        ));

        if ($reprogramados > 0) {
            $hallazgos[] = new Hallazgo(
                tipo: 'turnos_reprogramados',
                severidad: SeveridadHallazgo::INFORMATIVO,
                mensaje: "{$reprogramados} de los turnos a cancelar fueron creados por reprogramación y no siguen el patrón original.",
            );
        }

        return $hallazgos;
    }

    /**
     * @return array<string, mixed>
     */
    private function turnoResumen(TurnoExistente $turno): array
    {
        return [
            'id' => $turno->id,
            'fecha' => $turno->fecha,
            'hora_inicio' => $turno->horaInicio,
            'hora_fin' => $turno->horaFin,
            'estado' => $turno->estado->value,
            'estado_label' => $turno->estado->label(),
            'profesional_id' => $turno->profesionalId,
            'sala_id' => $turno->salaId,
            'lote_generacion_id' => $turno->loteGeneracionId,
        ];
    }
}
