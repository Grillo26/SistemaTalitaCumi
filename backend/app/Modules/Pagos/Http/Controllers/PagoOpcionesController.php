<?php

namespace App\Modules\Pagos\Http\Controllers;

use App\Models\User;
use App\Modules\Pagos\Enums\TipoEntidadPago;
use App\Modules\Pagos\Models\MedioPago;
use App\Modules\Pagos\Support\PagoAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catálogos para los formularios de pagos (medios activos del catálogo, RF-005).
 */
final class PagoOpcionesController
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => [
                'medios' => MedioPago::query()->activos()->get(['codigo', 'nombre'])
                    ->map(static fn (MedioPago $medio): array => [
                        'codigo' => $medio->codigo,
                        'nombre' => $medio->nombre,
                    ])
                    ->values(),
                'tipos_entidad' => collect(TipoEntidadPago::cases())
                    ->map(static fn (TipoEntidadPago $tipo): array => [
                        'codigo' => $tipo->value,
                        'nombre' => $tipo->label(),
                        'disponible' => $tipo->disponibleEnEtapa(),
                    ])
                    ->values(),
            ],
            'can' => PagoAbilities::for($actor),
        ]);
    }
}
