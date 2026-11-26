<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\MotivoNoRenovable;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Exceptions\RenovacionNoPermitidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\VigenciaEntidadNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\CambioTarifaRule;
use App\Modules\InscripcionesAtenciones\Support\VigenciaAbilities;

/**
 * [Vigencia y Renovación] RF-005 / RF-006 / CU-02.
 *
 * Reutiliza los previews de los submódulos de origen y agrega la comparación
 * entre tarifa anterior y tarifa vigente.
 */
final class PreviewRenovacionQuery
{
    public function __construct(
        private readonly GetRenovacionPreviewQuery $inscripcionPreview,
        private readonly GetPaqueteRenovacionPreviewQuery $paquetePreview,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(TipoEntidadVigencia $tipo, int $id, User $actor): array
    {
        if (! VigenciaAbilities::puedeRenovar($actor, $tipo)) {
            throw new RenovacionNoPermitidaException(MotivoNoRenovable::SIN_PERMISO_RENOVAR);
        }

        return $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? $this->inscripcion($id, $actor)
            : $this->paquete($id, $actor);
    }

    /**
     * @return array<string, mixed>
     */
    private function inscripcion(int $id, User $actor): array
    {
        $preview = ($this->inscripcionPreview)($id, $actor);
        $origen = InscripcionSala::query()->find($id);

        if ($origen === null) {
            throw new VigenciaEntidadNotFoundException(TipoEntidadVigencia::INSCRIPCION_SALA, $id);
        }

        $tarifa = $preview['tarifa'];
        $montoVigente = is_array($tarifa) ? (string) ($tarifa['monto'] ?? '') : null;
        $tarifaIdVigente = is_array($tarifa) ? (int) ($tarifa['tarifa_id'] ?? 0) : null;
        $montoAnterior = (string) $origen->monto_esperado;
        $cambio = is_array($tarifa)
            && CambioTarifaRule::hayCambio(
                (int) $origen->tarifa_id,
                $montoAnterior,
                $tarifaIdVigente,
                $montoVigente,
            );

        return [
            'tipo_entidad' => TipoEntidadVigencia::INSCRIPCION_SALA->value,
            'tipo_entidad_label' => TipoEntidadVigencia::INSCRIPCION_SALA->label(),
            'entidad_id' => (int) $origen->id,
            'entidad_origen_id' => (int) $origen->id,
            'paciente' => null,
            'tutor' => $preview['tutor'],
            'sucursal' => $preview['sucursal'],
            'servicio' => $preview['servicio'],
            'categoria' => $preview['categoria'],
            'sala' => $preview['sala'],
            'profesional' => null,
            'fecha_inicio' => $preview['fecha_inicio'],
            'fecha_fin' => $preview['fecha_fin'],
            'cantidad_sesiones' => null,
            'tarifa_anterior' => [
                'tarifa_id' => (int) $origen->tarifa_id,
                'monto' => $montoAnterior,
            ],
            'tarifa_vigente' => $tarifa,
            'monto_anterior' => $montoAnterior,
            'monto_vigente' => $montoVigente,
            'cambio_tarifa' => $cambio,
            'tarifa_disponible' => (bool) $preview['tarifa_disponible'],
            'renovable' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paquete(int $id, User $actor): array
    {
        $preview = ($this->paquetePreview)($id, $actor);
        $origen = PaqueteSesiones::query()->find($id);

        if ($origen === null) {
            throw new VigenciaEntidadNotFoundException(TipoEntidadVigencia::PAQUETE_SESIONES, $id);
        }

        $tarifa = $preview['tarifa'];
        $montoVigente = (string) ($preview['monto_total'] ?? '');
        $tarifaIdVigente = is_array($tarifa) ? (int) ($tarifa['tarifa_id'] ?? 0) : null;
        $montoAnterior = (string) $origen->monto_esperado;
        $cambio = is_array($tarifa)
            && CambioTarifaRule::hayCambio(
                (int) $origen->tarifa_id,
                $montoAnterior,
                $tarifaIdVigente,
                $montoVigente !== '' ? $montoVigente : null,
            );

        return [
            'tipo_entidad' => TipoEntidadVigencia::PAQUETE_SESIONES->value,
            'tipo_entidad_label' => TipoEntidadVigencia::PAQUETE_SESIONES->label(),
            'entidad_id' => (int) $origen->id,
            'entidad_origen_id' => (int) $origen->id,
            'paciente' => null,
            'tutor' => $preview['tutor'],
            'sucursal' => $preview['sucursal'],
            'servicio' => $preview['servicio'],
            'categoria' => $preview['categoria'],
            'sala' => null,
            'profesional' => $preview['profesional_id'],
            'fecha_inicio' => $preview['fecha_inicio'],
            'fecha_fin' => $preview['fecha_fin'],
            'cantidad_sesiones' => $preview['cantidad_sesiones'],
            'dias_semana' => $preview['dias_semana'],
            'hora' => $preview['hora'],
            'duracion_minutos' => $preview['duracion_minutos'],
            'tarifa_anterior' => [
                'tarifa_id' => (int) $origen->tarifa_id,
                'monto' => $montoAnterior,
            ],
            'tarifa_vigente' => $tarifa,
            'monto_anterior' => $montoAnterior,
            'monto_vigente' => $montoVigente !== '' ? $montoVigente : null,
            'monto_sesion' => $preview['monto_sesion'],
            'sesiones_estimadas' => $preview['sesiones_estimadas'],
            'advertencia' => $preview['advertencia'],
            'cambio_tarifa' => $cambio,
            'tarifa_disponible' => (bool) $preview['tarifa_disponible'],
            'renovable' => true,
        ];
    }
}
