<?php

namespace Database\Seeders;

use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use Illuminate\Database\Seeder;

/**
 * Categoría base de tarifa (sección 11 del consolidado).
 *
 * Idempotente. No crea tarifas de ejemplo.
 */
final class CategoriaTarifaSeeder extends Seeder
{
    public function run(): void
    {
        CategoriaTarifa::query()->firstOrCreate(
            ['nombre' => 'General'],
            [
                'descripcion' => 'Tarifa general del centro.',
                'activo' => true,
                'version' => 1,
            ],
        );
    }
}
