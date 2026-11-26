<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\MotivoNoRenovable;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Exceptions\CambioTarifaSinConfirmarException;
use App\Modules\InscripcionesAtenciones\Exceptions\RenovacionNoPermitidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\TarifaNoVigenteException;
use App\Modules\InscripcionesAtenciones\Exceptions\VigenciaEntidadNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Queries\PreviewMontoEsperadoQuery;
use App\Modules\InscripcionesAtenciones\Queries\PreviewMontoPaqueteQuery;
use App\Modules\InscripcionesAtenciones\Rules\CambioTarifaRule;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPeriod;
use App\Modules\InscripcionesAtenciones\Support\PaquetePeriod;
use App\Modules\InscripcionesAtenciones\Support\PaqueteSnapshot;
use App\Modules\InscripcionesAtenciones\Support\VigenciaAbilities;
use App\Modules\InscripcionesAtenciones\Support\VigenciaRenovacionResult;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;

/**
 * [Vigencia y Renovación] RF-005 / RF-006 / RN-004 / RN-005.
 *
 * Orquesta las Actions de renovación existentes. No duplica sus reglas de
 * fechas, paciente, servicio, sala o creación.
 */
final class RenovarDesdePanelAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly PreviewMontoEsperadoQuery $previewInscripcion,
        private readonly PreviewMontoPaqueteQuery $previewPaquete,
        private readonly RenovarInscripcionAction $renovarInscripcion,
        private readonly RenovarPaqueteAction $renovarPaquete,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(
        TipoEntidadVigencia $tipo,
        int $id,
        array $data,
        User $actor,
        ?string $ip = null,
    ): VigenciaRenovacionResult {
        if (! VigenciaAbilities::puedeRenovar($actor, $tipo)) {
            throw new RenovacionNoPermitidaException(MotivoNoRenovable::SIN_PERMISO_RENOVAR);
        }

        return DB::transaction(fn (): VigenciaRenovacionResult => $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? $this->renovarSala($id, $data, $actor, $ip)
            : $this->renovarPaquete($id, $data, $actor, $ip));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renovarSala(int $id, array $data, User $actor, ?string $ip): VigenciaRenovacionResult
    {
        $origen = InscripcionSala::query()->find($id);

        if ($origen === null) {
            throw new VigenciaEntidadNotFoundException(TipoEntidadVigencia::INSCRIPCION_SALA, $id);
        }

        if (! $this->access->puedeAcceder($actor->id, (int) $origen->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }

        $fechaInicio = isset($data['fecha_inicio'])
            ? (string) $data['fecha_inicio']
            : InscripcionPeriod::nextStart(
                $origen->fecha_fin->toDateString(),
            );
        $fechaFin = isset($data['fecha_fin'])
            ? (string) $data['fecha_fin']
            : InscripcionPeriod::defaultEnd($fechaInicio);
        $servicioId = isset($data['servicio_id'])
            ? (int) $data['servicio_id']
            : (int) $origen->servicio_id;
        $categoriaId = isset($data['categoria_tarifa_id'])
            ? (int) $data['categoria_tarifa_id']
            : (int) $origen->categoria_tarifa_id;

        $tarifa = ($this->previewInscripcion)(
            servicioId: $servicioId,
            sucursalId: (int) $origen->sucursal_id,
            categoriaTarifaId: $categoriaId,
            fechaInicio: $fechaInicio,
            actor: $actor,
        );

        if ($tarifa === null) {
            throw new TarifaNoVigenteException;
        }

        $montoVigente = $tarifa->monto;
        $montoVisto = (string) $data['monto_visto'];
        $cambio = CambioTarifaRule::hayCambio(
            (int) $origen->tarifa_id,
            (string) $origen->monto_esperado,
            $tarifa->tarifaId,
            $montoVigente,
        );

        CambioTarifaRule::assertMontoVisto(
            montoVisto: $montoVisto,
            montoVigente: $montoVigente,
            confirmado: (bool) ($data['confirmar_tarifa_cambio'] ?? false),
            cambio: $cambio,
            tarifaVigente: $tarifa->toArray(),
        );

        $nueva = ($this->renovarInscripcion)(
            $id,
            [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'servicio_id' => $servicioId,
                'categoria_tarifa_id' => $categoriaId,
                'observaciones' => $data['observaciones'] ?? null,
                'tarifa_id_esperada' => $tarifa->tarifaId,
                'confirmar_tarifa_cambio' => false,
            ],
            $actor,
            $ip,
        );

        $this->assertMontoFinal((string) $nueva->monto_esperado, $montoVisto, $tarifa->toArray());

        return new VigenciaRenovacionResult(
            tipo: TipoEntidadVigencia::INSCRIPCION_SALA,
            origenId: $id,
            nuevaId: (int) $nueva->id,
            monto: (string) $nueva->monto_esperado,
            fechaInicio: $nueva->fecha_inicio->toDateString(),
            fechaFin: $nueva->fecha_fin->toDateString(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renovarPaquete(int $id, array $data, User $actor, ?string $ip): VigenciaRenovacionResult
    {
        $origen = PaqueteSesiones::query()->find($id);

        if ($origen === null) {
            throw new VigenciaEntidadNotFoundException(TipoEntidadVigencia::PAQUETE_SESIONES, $id);
        }

        if (! $this->access->puedeAcceder($actor->id, (int) $origen->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }

        $fechaInicio = isset($data['fecha_inicio'])
            ? (string) $data['fecha_inicio']
            : PaquetePeriod::nextStart(
                $origen->fecha_fin->toDateString(),
            );
        $fechaFin = isset($data['fecha_fin'])
            ? (string) $data['fecha_fin']
            : PaquetePeriod::sameDurationEnd(
                $fechaInicio,
                $origen->fecha_inicio->toDateString(),
                $origen->fecha_fin->toDateString(),
            );
        $cantidadSesiones = isset($data['cantidad_sesiones'])
            ? (int) $data['cantidad_sesiones']
            : (int) $origen->cantidad_sesiones;
        $diasSemana = isset($data['dias_semana'])
            ? (array) $data['dias_semana']
            : $origen->dias_semana;
        $hora = isset($data['hora'])
            ? (string) $data['hora']
            : PaqueteSnapshot::horaCorta((string) $origen->hora);
        $duracion = isset($data['duracion_minutos'])
            ? (int) $data['duracion_minutos']
            : (int) $origen->duracion_minutos;
        $profesionalId = array_key_exists('profesional_id', $data)
            ? (is_numeric($data['profesional_id']) ? (int) $data['profesional_id'] : null)
            : $origen->profesional_id;

        $montoPreview = ($this->previewPaquete)(
            servicioId: (int) $origen->servicio_id,
            sucursalId: (int) $origen->sucursal_id,
            categoriaTarifaId: (int) $origen->categoria_tarifa_id,
            fechaInicio: $fechaInicio,
            cantidadSesiones: $cantidadSesiones,
            actor: $actor,
            diasSemana: $diasSemana,
            fechaFin: $fechaFin,
        );

        $tarifa = $montoPreview['tarifa'] ?? null;

        if (! is_array($tarifa) || ($montoPreview['monto_total'] ?? null) === null) {
            throw new TarifaNoVigenteException;
        }

        $montoVigente = (string) $montoPreview['monto_total'];
        $montoVisto = (string) $data['monto_visto'];
        $cambio = CambioTarifaRule::hayCambio(
            (int) $origen->tarifa_id,
            (string) $origen->monto_esperado,
            (int) ($tarifa['tarifa_id'] ?? 0),
            $montoVigente,
        );

        CambioTarifaRule::assertMontoVisto(
            montoVisto: $montoVisto,
            montoVigente: $montoVigente,
            confirmado: (bool) ($data['confirmar_tarifa_cambio'] ?? false),
            cambio: $cambio,
            tarifaVigente: $tarifa,
        );

        $nuevo = ($this->renovarPaquete)(
            $id,
            [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'cantidad_sesiones' => $cantidadSesiones,
                'dias_semana' => $diasSemana,
                'hora' => $hora,
                'duracion_minutos' => $duracion,
                'profesional_id' => $profesionalId,
                'observaciones' => $data['observaciones'] ?? null,
                'tarifa_id_esperada' => (int) $tarifa['tarifa_id'],
                'confirmar_tarifa_cambio' => false,
            ],
            $actor,
            $ip,
        );

        $this->assertMontoFinal((string) $nuevo->monto_esperado, $montoVisto, $tarifa);

        return new VigenciaRenovacionResult(
            tipo: TipoEntidadVigencia::PAQUETE_SESIONES,
            origenId: $id,
            nuevaId: (int) $nuevo->id,
            monto: (string) $nuevo->monto_esperado,
            fechaInicio: $nuevo->fecha_inicio->toDateString(),
            fechaFin: $nuevo->fecha_fin->toDateString(),
        );
    }

    /**
     * @param  array<string, mixed>  $tarifa
     */
    private function assertMontoFinal(string $montoFinal, string $montoVisto, array $tarifa): void
    {
        if (bccomp($montoFinal, $montoVisto, 2) !== 0) {
            throw new CambioTarifaSinConfirmarException(
                tarifa,
                $montoVisto,
                'El monto cambió durante la renovación. Revisá la tarifa vigente y volvé a confirmar.',
            );
        }
    }
}
