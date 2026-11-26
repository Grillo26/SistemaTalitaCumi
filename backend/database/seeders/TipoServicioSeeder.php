<?php

namespace Database\Seeders;

use App\Modules\CatalogoServicios\Models\TipoServicio;
use Illuminate\Database\Seeder;

/**
 * Tipos de servicio base (RN-C09).
 *
 * El catálogo es configurable: el cliente puede agregar, renombrar o
 * desactivar tipos desde la UI. Idempotente.
 */
final class TipoServicioSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Entrevista',
            'Diagnóstico / Evaluación',
            'Terapia individual',
            'Sala grupal',
        ] as $nombre) {
            TipoServicio::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true, 'version' => 1],
            );
        }
    }
}
