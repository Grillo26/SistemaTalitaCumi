<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteYaRenovadoException;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\PaquetePeriod;
use App\Modules\InscripcionesAtenciones\Support\ProfesionalPaqueteGuard;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;

/**
 * CU-02: propuesta de fechas y tarifa para renovar un paquete.
 */
final class GetPaqueteRenovacionPreviewQuery
{
    public function __construct(
        private readonly GetPaqueteQuery $detalle,
        private readonly PreviewMontoPaqueteQuery $preview,
        private readonly PatientDirectoryContract $patients,
        private readonly ProfesionalPaqueteGuard $profesionales,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $id, User $actor): array
    {
        $detalle = ($this->detalle)($id, $actor);
        $paquete = $detalle->paquete;

        PaqueteStateTransitionRule::assertPuedeRenovar($paquete->estado);

        $existente = PaqueteSesiones::query()
            ->where('paquete_origen_id', $id)
            ->where('estado', '!=', 'retirado')
            ->latest('id')
            ->first();

        if ($existente !== null) {
            throw new PaqueteYaRenovadoException((int) $existente->id);
        }

        $this->patients->asegurarPermiteInscripcion((int) $paquete->paciente_id);
        $this->profesionales->assertDisponible(
            $paquete->profesional_id === null ? null : (int) $paquete->profesional_id,
            (int) $paquete->sucursal_id,
        );

        $fechaInicio = PaquetePeriod::nextStart($paquete->fecha_fin->toDateString());
        $fechaFin = PaquetePeriod::sameDurationEnd(
            $fechaInicio,
            $paquete->fecha_inicio->toDateString(),
            $paquete->fecha_fin->toDateString(),
        );

        $monto = ($this->preview)(
            servicioId: (int) $paquete->servicio_id,
            sucursalId: (int) $paquete->sucursal_id,
            categoriaTarifaId: (int) $paquete->categoria_tarifa_id,
            fechaInicio: $fechaInicio,
            cantidadSesiones: (int) $paquete->cantidad_sesiones,
            actor: $actor,
            diasSemana: $paquete->dias_semana,
            fechaFin: $fechaFin,
        );

        return [
            'paquete_origen_id' => (int) $paquete->id,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'cantidad_sesiones' => (int) $paquete->cantidad_sesiones,
            'dias_semana' => $paquete->dias_semana,
            'hora' => substr((string) $paquete->hora, 0, 5),
            'duracion_minutos' => (int) $paquete->duracion_minutos,
            'profesional_id' => $paquete->profesional_id,
            'servicio' => $detalle->servicio,
            'categoria' => $detalle->categoria,
            'sucursal' => $detalle->sucursal,
            'tutor' => $detalle->tutor->toArray(),
            'tarifa' => $monto['tarifa'],
            'tarifa_disponible' => $monto['tarifa_disponible'],
            'monto_sesion' => $monto['monto_sesion'],
            'monto_total' => $monto['monto_total'],
            'sesiones_estimadas' => $monto['sesiones_estimadas'],
            'advertencia' => $monto['advertencia'],
        ];
    }
}
