<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoOrigenProgramacion;
use App\Modules\AgendaTurnos\Exceptions\EstadoTurnoDesactualizadoException;
use App\Modules\AgendaTurnos\Exceptions\MotivoObligatorioException;
use App\Modules\AgendaTurnos\Exceptions\OrigenNoReprogramableException;
use App\Modules\AgendaTurnos\Exceptions\TurnoFueraDeVigenciaException;
use App\Modules\AgendaTurnos\Exceptions\TurnoNoModificableException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\DisponibilidadReprogramacionRule;
use App\Modules\AgendaTurnos\Rules\EstadoEsperadoRule;
use App\Modules\AgendaTurnos\Rules\MotivoObligatorioRule;
use App\Modules\AgendaTurnos\Rules\NuevaFechaRule;
use App\Modules\AgendaTurnos\Rules\OrigenReprogramableRule;
use App\Modules\AgendaTurnos\Rules\SesionGrupalRule;
use App\Modules\AgendaTurnos\Rules\TurnoModificableRule;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosContract;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosResumen;

/**
 * Cálculo compartido por el preview y la confirmación de reprogramación.
 *
 * No escribe nada. La Action lo invoca después de tomar los locks y vuelve a
 * validar el estado actual.
 */
final class ReprogramacionCalculator
{
    public function __construct(
        private readonly OrigenTurnosContract $origenes,
        private readonly MotivoTurnoResolver $motivos,
        private readonly TurnoModificableRule $modificable,
        private readonly EstadoEsperadoRule $estadoEsperado,
        private readonly MotivoObligatorioRule $motivoRule,
        private readonly NuevaFechaRule $nuevaFecha,
        private readonly OrigenReprogramableRule $origenRule,
        private readonly TurnosDisponibilidadResolver $disponibilidad,
        private readonly DisponibilidadReprogramacionRule $disponibilidadRule,
        private readonly FeriadosResolver $feriados,
        private readonly ProgramacionGuard $guard,
        private readonly SalaCambioValidator $salaValidator,
        private readonly AgendaClock $clock,
    ) {}

    public function calcular(
        Turno $turno,
        ReprogramacionInput $input,
        User $actor,
    ): ReprogramacionCalculada {
        if (! $this->modificable->permite($turno->estado)) {
            throw new TurnoNoModificableException(
                $turno->estado,
                $this->modificable->mensaje($turno->estado, 'reprogramar')
                    ?? 'El turno no se puede reprogramar.',
            );
        }

        if (! $this->estadoEsperado->coincide($turno->estado, $input->estadoEsperado)) {
            throw new EstadoTurnoDesactualizadoException($turno->estado);
        }

        $motivo = $this->motivos->obtener($input->motivoId);
        $errores = $this->motivoRule->errores($motivo, 'reprogramar', $input->motivoTexto);

        if ($errores !== [] || $motivo === null) {
            throw new MotivoObligatorioException($errores);
        }

        $origen = $this->origen($turno);
        $estadoOrigen = $origen === null
            ? null
            : EstadoOrigenProgramacion::desdePersistido($origen->estadoEfectivo);

        if (! $this->origenRule->permite($estadoOrigen)) {
            throw new OrigenNoReprogramableException($estadoOrigen ?? EstadoOrigenProgramacion::DESCONOCIDO);
        }

        $profesionalId = $input->profesionalProporcionado
            ? $input->profesionalId
            : ($turno->profesional_id === null ? null : (int) $turno->profesional_id);

        $salaId = $input->salaProporcionada
            ? $input->salaId
            : ($turno->sala_id === null ? null : (int) $turno->sala_id);

        $erroresFecha = $this->nuevaFecha->errores(
            fecha: $input->fecha,
            horaInicio: $input->horaInicio,
            hoy: $this->clock->hoy(),
            horaActual: $this->clock->horaActual(),
            periodoInicio: $origen?->fechaInicio,
            periodoFin: $origen?->fechaFin,
            duracionMinutos: (int) $turno->duracion_minutos,
        );

        if ($this->nuevaFecha->esMismoSlot(
            $input->fecha,
            $input->horaInicio,
            $turno->fecha->toDateString(),
            (string) $turno->hora_inicio,
        )) {
            $erroresFecha[] = 'La nueva fecha y hora deben ser distintas a las originales.';
        }

        if ($erroresFecha !== []) {
            throw new TurnoFueraDeVigenciaException($erroresFecha);
        }

        $this->guard->asegurarProfesional($profesionalId, (int) $turno->sucursal_id);
        $this->salaValidator->validar(
            $salaId,
            (int) $turno->sucursal_id,
            $turno->inscripcion_sala_id !== null,
        );

        $horaFin = $this->nuevaFecha->horaFin($input->horaInicio, (int) $turno->duracion_minutos);
        $sesionKey = SesionGrupalRule::clave(
            $salaId,
            $input->fecha,
            $input->horaInicio,
            $horaFin,
            (int) $turno->servicio_id,
        );

        $hallazgos = $this->disponibilidadRule->evaluar(
            new TurnoCandidato(
                pacienteId: (int) $turno->paciente_id,
                profesionalId: $profesionalId,
                salaId: $salaId,
                servicioId: (int) $turno->servicio_id,
                fecha: $input->fecha,
                horaInicio: $input->horaInicio,
                horaFin: $horaFin,
                sesionKey: $sesionKey,
            ),
            $this->disponibilidad->paraCambio(
                (int) $turno->paciente_id,
                $profesionalId,
                $salaId,
                $input->fecha,
            ),
            (int) $turno->id,
            $this->feriados->mapa((int) $turno->sucursal_id, $input->fecha, $input->fecha),
        );

        $bloqueos = array_filter(
            $hallazgos,
            static fn (Hallazgo $hallazgo): bool => $hallazgo->severidad->value === 'bloqueo',
        );
        $advertencias = array_filter(
            $hallazgos,
            static fn (Hallazgo $hallazgo): bool => $hallazgo->severidad->value === 'advertencia',
        );

        return new ReprogramacionCalculada(
            turno: $turno,
            motivo: $motivo,
            original: $this->original($turno),
            nuevo: [
                'fecha' => $input->fecha,
                'hora_inicio' => $input->horaInicio,
                'hora_fin' => $horaFin,
                'duracion_minutos' => (int) $turno->duracion_minutos,
                'profesional_id' => $profesionalId,
                'sala_id' => $salaId,
                'servicio_id' => (int) $turno->servicio_id,
            ],
            hallazgos: $hallazgos,
            puedeConfirmar: $bloqueos === []
                && ($advertencias === [] || $input->confirmarAdvertencias),
            requiereConfirmarAdvertencias: $advertencias !== [],
            origen: $origen === null ? null : [
                'tipo' => $origen->tipo,
                'id' => $origen->id,
                'fecha_inicio' => $origen->fechaInicio,
                'fecha_fin' => $origen->fechaFin,
                'estado_efectivo' => $estadoOrigen?->value,
                'estado_efectivo_label' => $estadoOrigen?->label(),
            ],
        );
    }

    private function origen(Turno $turno): ?OrigenTurnosResumen
    {
        if ($turno->paquete_sesiones_id !== null) {
            return $this->origenes->obtener('paquete_sesiones', (int) $turno->paquete_sesiones_id);
        }

        if ($turno->inscripcion_sala_id !== null) {
            return $this->origenes->obtener('inscripcion_sala', (int) $turno->inscripcion_sala_id);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function original(Turno $turno): array
    {
        return [
            'id' => (int) $turno->id,
            'fecha' => $turno->fecha->toDateString(),
            'hora_inicio' => (string) $turno->hora_inicio,
            'hora_fin' => (string) $turno->hora_fin,
            'duracion_minutos' => (int) $turno->duracion_minutos,
            'estado' => $turno->estado->value,
            'estado_label' => $turno->estado->label(),
            'paciente_id' => (int) $turno->paciente_id,
            'profesional_id' => $turno->profesional_id === null ? null : (int) $turno->profesional_id,
            'sala_id' => $turno->sala_id === null ? null : (int) $turno->sala_id,
            'servicio_id' => (int) $turno->servicio_id,
            'sucursal_id' => (int) $turno->sucursal_id,
        ];
    }
}
