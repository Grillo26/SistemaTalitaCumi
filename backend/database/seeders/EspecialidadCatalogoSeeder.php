<?php

namespace Database\Seeders;

use App\Modules\Profesionales\Models\Especialidad;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de especialidades — PROPUESTO - VALIDAR.
 *
 * Solo siembra en una instalación sin especialidades. Si el cliente ya cargó
 * o renombró el catálogo, no se vuelve a sembrar ni se pisan sus cambios.
 * El documento consolidado menciona “6 especialidades base” pero solo lista
 * cinco; se siembran las cinco listadas.
 */
final class EspecialidadCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        if (Especialidad::query()->exists()) {
            return;
        }

        foreach ($this->catalog() as $nombre) {
            Especialidad::query()->create([
                'nombre' => $nombre,
                'descripcion' => 'Especialidad base propuesta — validar con el cliente.',
                'activo' => true,
                'version' => 1,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function catalog(): array
    {
        return [
            'Psicología',
            'Psicopedagogía',
            'Terapia Ocupacional',
            'Fisioterapia',
            'Terapia de Lenguaje',
        ];
    }
}
