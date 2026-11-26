<?php

namespace Database\Seeders;

use App\Modules\CatalogoServicios\Models\Area;
use Illuminate\Database\Seeder;

/**
 * Áreas base del catálogo (sección 11 del consolidado).
 *
 * Idempotente. No crea servicios de ejemplo. Los colores se toman de los
 * tokens del proyecto: brand-500 y accent-500.
 */
final class AreaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['nombre' => 'Intervención Terapéutica', 'color' => '#671C61'],
            ['nombre' => 'Salas Grupales', 'color' => '#F9733F'],
        ] as $area) {
            Area::query()->firstOrCreate(
                ['nombre' => $area['nombre']],
                [
                    'descripcion' => null,
                    'color' => $area['color'],
                    'activo' => true,
                    'version' => 1,
                ],
            );
        }
    }
}
