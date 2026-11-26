<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResumen;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionYaRenovadaException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPeriod;
use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Exceptions\TutorInactiveException;

/**
 * CU-02: propuesta de fechas y tarifa para renovar.
 */
final class GetRenovacionPreviewQuery
{
    public function __construct(
        private readonly GetInscripcionQuery $detalle,
        private readonly PreviewMontoEsperadoQuery $preview,
        private readonly PatientDirectoryContract $patients,
        private readonly SalaDirectoryContract $salas,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $id, User $actor): array
    {
        $detalle = ($this->detalle)($id, $actor);
        $inscripcion = $detalle->inscripcion;

        InscripcionStateTransitionRule::assertPuedeRenovar($inscripcion->estado);

        $existente = InscripcionSala::query()
            ->where('inscripcion_origen_id', $id)
            ->where('estado', '!=', 'retirada')
            ->latest('id')
            ->first();

        if ($existente !== null) {
            throw new InscripcionYaRenovadaException((int) $existente->id);
        }

        $this->patients->asegurarPermiteInscripcion((int) $inscripcion->paciente_id);
        $this->salas->asegurarActivaEnSucursal(
            (int) $inscripcion->sala_id,
            (int) $inscripcion->sucursal_id,
        );

        if (! $detalle->tutor->activo) {
            throw new TutorInactiveException;
        }

        $fechaInicio = InscripcionPeriod::nextStart($inscripcion->fecha_fin->toDateString());
        $fechaFin = InscripcionPeriod::defaultEnd($fechaInicio);

        $tarifa = ($this->preview)(
            servicioId: (int) $inscripcion->servicio_id,
            sucursalId: (int) $inscripcion->sucursal_id,
            categoriaTarifaId: (int) $inscripcion->categoria_tarifa_id,
            fechaInicio: $fechaInicio,
            actor: $actor,
        );

        return [
            'inscripcion_origen_id' => (int) $inscripcion->id,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'servicio' => $detalle->servicio,
            'categoria' => $detalle->categoria,
            'sala' => $detalle->sala,
            'sucursal' => $detalle->sucursal,
            'tutor' => $detalle->tutor->toArray(),
            'tarifa' => $tarifa?->toArray(),
            'tarifa_disponible' => $tarifa instanceof TarifaAplicableResumen,
        ];
    }
}
