<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Rules\TarifaOverlapRule;

/**
 * Consulta de rangos existentes para TarifaOverlapRule.
 *
 * La regla sigue siendo pura; esta clase solo lee y bloquea las filas.
 */
final class TarifaOverlapGuard
{
    public function assertNoOverlap(
        int $servicioId,
        ?int $sucursalId,
        int $categoriaTarifaId,
        string $vigenteDesde,
        ?string $vigenteHasta,
        ?int $ignoreId = null,
    ): void {
        $ranges = Tarifa::query()
            ->with(['sucursal', 'categoria'])
            ->where('servicio_id', $servicioId)
            ->where('categoria_tarifa_id', $categoriaTarifaId)
            ->sucursalExacta($sucursalId)
            ->when($ignoreId !== null, static fn ($query) => $query->whereKeyNot($ignoreId))
            ->lockForUpdate()
            ->get()
            ->map(static fn (Tarifa $tarifa): array => [
                'id' => $tarifa->id,
                'vigente_desde' => $tarifa->vigente_desde->toDateString(),
                'vigente_hasta' => $tarifa->vigente_hasta?->toDateString(),
                'monto' => (string) $tarifa->monto,
                'sucursal' => $tarifa->sucursal?->nombre ?? 'Global',
                'categoria' => $tarifa->categoria?->nombre,
            ])
            ->all();

        TarifaOverlapRule::assertNoOverlap($vigenteDesde, $vigenteHasta, $ranges);
    }
}
