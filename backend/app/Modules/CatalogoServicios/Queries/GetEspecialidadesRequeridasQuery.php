<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoNotFoundException;
use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosCacheKeys;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosModule;
use Illuminate\Support\Facades\Cache;

/**
 * Especialidades requeridas por tipo, cacheadas para agenda/diagnósticos
 * (RF-D08 / RF-D11).
 */
final class GetEspecialidadesRequeridasQuery
{
    /**
     * @return list<array{especialidad_id: int, nombre: string, cantidad_minima: int, activo: bool}>
     */
    public function __invoke(int $tipoDiagnosticoId): array
    {
        $items = Cache::remember(
            CatalogoServiciosCacheKeys::tiposDiagnostico(),
            CatalogoServiciosModule::CACHE_TTL_SECONDS,
            static fn (): array => TipoDiagnostico::query()
                ->with('especialidades')
                ->get()
                ->mapWithKeys(static fn (TipoDiagnostico $tipo): array => [
                    $tipo->id => $tipo->especialidades
                        ->map(static fn ($especialidad): array => [
                            'especialidad_id' => $especialidad->id,
                            'nombre' => $especialidad->nombre,
                            'cantidad_minima' => (int) $especialidad->pivot->cantidad_minima,
                            'activo' => (bool) $especialidad->activo,
                        ])
                        ->values()
                        ->all(),
                ])
                ->all(),
        );

        if (! array_key_exists($tipoDiagnosticoId, $items)) {
            throw new TipoDiagnosticoNotFoundException($tipoDiagnosticoId);
        }

        return $items[$tipoDiagnosticoId];
    }
}
