<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResolverContract;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcionSala;
use App\Modules\InscripcionesAtenciones\Events\InscripcionUpdated;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionDuplicadaException;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\TarifaCambioException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionObservacionesRule;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionOverlapRule;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionPeriodoRule;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Rules\TarifaVigenteRule;
use App\Modules\InscripcionesAtenciones\Support\InscripcionSnapshot;
use App\Modules\InscripcionesAtenciones\Support\SalaGrupalServicioGuard;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * RF-009: modificación de fechas, tarifa/categoría y observaciones con
 * bitácora antes/después. Paciente, tutor, sucursal y sala son inmutables.
 */
final class UpdateInscripcionAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly TarifaAplicableResolverContract $tarifas,
        private readonly SalaGrupalServicioGuard $servicios,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(int $id, array $data, User $actor, ?string $ip = null): InscripcionSala
    {
        try {
            return DB::transaction(function () use ($id, $data, $actor, $ip): InscripcionSala {
                $inscripcion = InscripcionSala::query()->lockForUpdate()->find($id);

                if ($inscripcion === null) {
                    throw new InscripcionNotFoundException($id);
                }

                if (! $this->access->puedeAcceder($actor->id, (int) $inscripcion->sucursal_id)) {
                    throw new BranchAccessDeniedException;
                }

                InscripcionStateTransitionRule::assertPuedeEditar($inscripcion->estado);

                $before = InscripcionSnapshot::from($inscripcion);

                $fechaInicio = array_key_exists('fecha_inicio', $data)
                    ? (string) $data['fecha_inicio']
                    : $inscripcion->fecha_inicio->toDateString();
                $fechaFin = array_key_exists('fecha_fin', $data)
                    ? (string) $data['fecha_fin']
                    : $inscripcion->fecha_fin->toDateString();
                $servicioId = array_key_exists('servicio_id', $data)
                    ? (int) $data['servicio_id']
                    : (int) $inscripcion->servicio_id;
                $categoriaId = array_key_exists('categoria_tarifa_id', $data)
                    ? (int) $data['categoria_tarifa_id']
                    : (int) $inscripcion->categoria_tarifa_id;

                InscripcionPeriodoRule::assertValido($fechaInicio, $fechaFin);
                InscripcionObservacionesRule::assertValidas(
                    array_key_exists('observaciones', $data) && is_string($data['observaciones'])
                        ? $data['observaciones']
                        : ($data['observaciones'] ?? null),
                );

                $recalcularTarifa = $fechaInicio !== $inscripcion->fecha_inicio->toDateString()
                    || $servicioId !== (int) $inscripcion->servicio_id
                    || $categoriaId !== (int) $inscripcion->categoria_tarifa_id;

                $nuevaTarifaId = (int) $inscripcion->tarifa_id;
                $nuevoMonto = (string) $inscripcion->monto_esperado;
                $nuevaCategoriaId = (int) $inscripcion->categoria_tarifa_id;

                if ($recalcularTarifa) {
                    $this->servicios->assertServicioValido($servicioId);

                    $tarifa = $this->tarifas->resolver(
                        servicioId: $servicioId,
                        sucursalId: (int) $inscripcion->sucursal_id,
                        categoriaTarifaId: $categoriaId,
                        fecha: $fechaInicio,
                    );

                    TarifaVigenteRule::assertVigente($tarifa, $fechaInicio);

                    if ($tarifa->tarifaId !== (int) $inscripcion->tarifa_id) {
                        $confirmar = (bool) ($data['confirmar_tarifa_cambio'] ?? false);
                        $esperada = isset($data['tarifa_id_esperada'])
                            ? (int) $data['tarifa_id_esperada']
                            : null;

                        if (! $confirmar && $esperada !== $tarifa->tarifaId) {
                            throw new TarifaCambioException($tarifa->toArray());
                        }

                        $nuevaTarifaId = $tarifa->tarifaId;
                        $nuevaCategoriaId = $tarifa->categoriaTarifaId;
                        $nuevoMonto = $tarifa->monto;
                    }
                }

                $periodos = InscripcionSala::query()
                    ->where('paciente_id', (int) $inscripcion->paciente_id)
                    ->where('sala_id', (int) $inscripcion->sala_id)
                    ->whereIn('estado', [
                        EstadoInscripcionSala::ACTIVA->value,
                        EstadoInscripcionSala::EN_PAUSA->value,
                    ])
                    ->whereKeyNot($inscripcion->id)
                    ->lockForUpdate()
                    ->get(['id', 'fecha_inicio', 'fecha_fin'])
                    ->map(static fn (InscripcionSala $item): array => [
                        'id' => (int) $item->id,
                        'fecha_inicio' => $item->fecha_inicio->toDateString(),
                        'fecha_fin' => $item->fecha_fin->toDateString(),
                    ])
                    ->all();

                InscripcionOverlapRule::assertSinSolapamiento(
                    $periodos,
                    $fechaInicio,
                    $fechaFin,
                    (int) $inscripcion->id,
                );

                $payload = [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'servicio_id' => $servicioId,
                    'tarifa_id' => $nuevaTarifaId,
                    'categoria_tarifa_id' => $nuevaCategoriaId,
                    'monto_esperado' => $nuevoMonto,
                ];

                if (array_key_exists('observaciones', $data)) {
                    $observaciones = $data['observaciones'];
                    $payload['observaciones'] = is_string($observaciones) && $observaciones !== ''
                        ? $observaciones
                        : null;
                }

                $inscripcion->fill($payload)->save();
                $inscripcion->refresh();

                $after = InscripcionSnapshot::from($inscripcion);
                $changed = [];

                foreach ($after as $key => $value) {
                    if (($before[$key] ?? null) !== $value) {
                        $changed[] = $key;
                    }
                }

                if ($changed !== []) {
                    event(new InscripcionUpdated(
                        inscripcionId: (int) $inscripcion->id,
                        before: $before,
                        after: $after,
                        changedFields: $changed,
                        actorId: $actor->id,
                        ip: $ip,
                    ));
                }

                return $inscripcion;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23P01'
                || str_contains($exception->getMessage(), 'inscripcion_sala_sin_solapamiento')) {
                throw new InscripcionDuplicadaException([
                    'inscripcion_id' => $id,
                ]);
            }

            throw $exception;
        }
    }
}
