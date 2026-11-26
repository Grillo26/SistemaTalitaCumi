<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResumen;
use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaInactivaException;
use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosCacheKeys;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosModule;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use Illuminate\Support\Facades\Cache;

/**
 * Resolución de tarifa aplicable (RF-T08 / RN-T05 / RF-T16).
 *
 * Precedencia: sucursal > global. La lista activa se cachea 60 s; la
 * invalidación la hace InvalidateCatalogoServiciosCache.
 */
final class ResolveTarifaAplicableQuery
{
    public function __construct(private readonly BranchDirectoryContract $branches) {}

    public function __invoke(
        int $servicioId,
        int $sucursalId,
        int $categoriaTarifaId,
        string $fecha,
    ): ?TarifaAplicableResumen {
        $servicio = Servicio::query()->find($servicioId);

        if ($servicio === null) {
            throw new ServicioNotFoundException($servicioId);
        }

        if (! $servicio->activo) {
            throw new ServicioInactivoException($servicioId);
        }

        $categoria = CategoriaTarifa::query()->find($categoriaTarifaId);

        if ($categoria === null) {
            throw new CategoriaTarifaNotFoundException($categoriaTarifaId);
        }

        if (! $categoria->activo) {
            throw new CategoriaTarifaInactivaException($categoriaTarifaId);
        }

        $this->branches->asegurarActiva($sucursalId);

        $items = Cache::remember(
            CatalogoServiciosCacheKeys::tarifas(),
            CatalogoServiciosModule::CACHE_TTL_SECONDS,
            static fn (): array => Tarifa::query()
                ->with(['servicio', 'categoria', 'sucursal'])
                ->where('activo', true)
                ->get()
                ->map(static fn (Tarifa $tarifa): array => [
                    'id' => $tarifa->id,
                    'servicio_id' => $tarifa->servicio_id,
                    'servicio_nombre' => $tarifa->servicio?->nombre,
                    'categoria_tarifa_id' => $tarifa->categoria_tarifa_id,
                    'categoria_nombre' => $tarifa->categoria?->nombre,
                    'sucursal_id' => $tarifa->sucursal_id,
                    'sucursal_nombre' => $tarifa->sucursal?->nombre,
                    'monto' => (string) $tarifa->monto,
                    'vigente_desde' => $tarifa->vigente_desde?->toDateString(),
                    'vigente_hasta' => $tarifa->vigente_hasta?->toDateString(),
                ])
                ->all(),
        );

        $candidates = array_values(array_filter(
            $items,
            static function (array $item) use ($servicioId, $sucursalId, $categoriaTarifaId, $fecha): bool {
                if ((int) $item['servicio_id'] !== $servicioId
                    || (int) $item['categoria_tarifa_id'] !== $categoriaTarifaId) {
                    return false;
                }

                if ($item['sucursal_id'] !== null && (int) $item['sucursal_id'] !== $sucursalId) {
                    return false;
                }

                if ($item['vigente_desde'] > $fecha) {
                    return false;
                }

                if ($item['vigente_hasta'] !== null && $item['vigente_hasta'] < $fecha) {
                    return false;
                }

                return true;
            },
        ));

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static function (array $a, array $b): int {
            $aBranch = $a['sucursal_id'] !== null ? 1 : 0;
            $bBranch = $b['sucursal_id'] !== null ? 1 : 0;

            if ($aBranch !== $bBranch) {
                return $bBranch <=> $aBranch;
            }

            if ($a['vigente_desde'] !== $b['vigente_desde']) {
                return $b['vigente_desde'] <=> $a['vigente_desde'];
            }

            return (int) $b['id'] <=> (int) $a['id'];
        });

        $selected = $candidates[0];
        $selected['origen'] = $selected['sucursal_id'] !== null ? 'sucursal' : 'global';

        return TarifaAplicableResumen::fromArray($selected);
    }
}
