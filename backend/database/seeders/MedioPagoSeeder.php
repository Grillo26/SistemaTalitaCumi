<?php

namespace Database\Seeders;

use App\Modules\Pagos\Models\MedioPago;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de medios de pago (08.01/RF-005, RN-002). Idempotente:
 * no pisa nombre ni activación de un medio existente.
 */
final class MedioPagoSeeder extends Seeder
{
    public function run(): void
    {
        $medios = [
            ['codigo' => 'efectivo', 'nombre' => 'Efectivo', 'orden' => 1],
            ['codigo' => 'qr', 'nombre' => 'QR', 'orden' => 2],
        ];

        foreach ($medios as $medio) {
            MedioPago::query()->firstOrCreate(
                ['codigo' => $medio['codigo']],
                ['nombre' => $medio['nombre'], 'activo' => true, 'orden' => $medio['orden']],
            );
        }
    }
}
