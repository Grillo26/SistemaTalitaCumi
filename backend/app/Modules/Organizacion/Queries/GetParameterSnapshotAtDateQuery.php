<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Exceptions\ParameterSnapshotNotFoundException;
use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Parametro;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Reconstruye la configuración vigente de un parámetro en una fecha (RNF-013).
 *
 * La bitácora es la fuente de verdad: el seeder deja un registro CREAR inicial
 * y cada edición/restauración un snapshot completo. No se inventa un valor si
 * no hay registro.
 */
final class GetParameterSnapshotAtDateQuery
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $id, CarbonInterface|string $fecha): array
    {
        if (! Parametro::query()->whereKey($id)->exists()) {
            throw new ParameterNotFoundException($id);
        }

        // La bitácora usa `CURRENT_TIMESTAMP` y PostgreSQL/SQLite la guardan en
        // UTC. La fecha límite se convierte a UTC para comparar correctamente.
        $at = $fecha instanceof CarbonInterface
            ? $fecha->copy()->utc()
            : Carbon::parse($fecha)->endOfDay()->utc();

        $entry = BitacoraEntry::query()
            ->where('entidad', 'parametro')
            ->where('entidad_id', $id)
            ->where('created_at', '<=', $at)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($entry === null || ! is_array($entry->datos_nuevos)) {
            throw new ParameterSnapshotNotFoundException($id, $at->toDateString());
        }

        return $entry->datos_nuevos;
    }
}
