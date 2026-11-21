<?php

namespace Database\Seeders;

use App\Modules\Organizacion\Models\Area;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Models\TurnoCatalogo;
use Illuminate\Database\Seeder;

/**
 * Catálogos iniciales del submódulo de salas y ambientes.
 *
 * Las áreas provienen del schema del módulo 05 y se cargan aquí solo como
 * prerequisito de solo lectura para `sala.area_id`.
 */
final class OrganizacionSalasAmbientesCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['nombre' => 'Salas Grupales', 'color' => '#10B981'],
            ['nombre' => 'Intervención Terapéutica', 'color' => '#3B82F6'],
        ] as $area) {
            Area::query()->firstOrCreate(
                ['nombre' => $area['nombre']],
                ['color' => $area['color'], 'activo' => true],
            );
        }

        foreach ([
            'Preescolar',
            'Atención temprana',
            'Estimulación temprana',
            'Escolar',
        ] as $nombre) {
            TipoSala::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true, 'version' => 1],
            );
        }

        foreach ([
            ['nombre' => 'Mañana', 'orden' => 1],
            ['nombre' => 'Tarde', 'orden' => 2],
        ] as $turno) {
            TurnoCatalogo::query()->firstOrCreate(
                ['nombre' => $turno['nombre']],
                ['orden' => $turno['orden'], 'activo' => true, 'version' => 1],
            );
        }
    }
}
