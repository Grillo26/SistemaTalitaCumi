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
            ['nombre' => 'Entrevista', 'codigo' => 'entrevista'],
            ['nombre' => 'Diagnóstico / Evaluación', 'codigo' => 'diagnostico_evaluacion'],
            ['nombre' => 'Terapia individual', 'codigo' => 'terapia_individual'],
            ['nombre' => 'Sala grupal', 'codigo' => 'sala_grupal'],
        ] as $tipo) {
            $existing = TipoServicio::query()
                ->whereRaw('lower(trim(nombre)) = ?', [mb_strtolower($tipo['nombre'])])
                ->first();

            if ($existing !== null) {
                if ($existing->codigo !== $tipo['codigo']) {
                    $existing->forceFill(['codigo' => $tipo['codigo']])->save();
                }

                continue;
            }

            TipoServicio::query()->create([
                'nombre' => $tipo['nombre'],
                'codigo' => $tipo['codigo'],
                'activo' => true,
                'version' => 1,
            ]);
        }
    }
}
