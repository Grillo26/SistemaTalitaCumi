<?php

namespace Database\Seeders;

use App\Modules\Pacientes\Models\ConsentimientoTipo;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de consentimientos (04.03 Fase B).
 *
 * PROPUESTO - VALIDAR CON EL CLIENTE. Superadmin puede editar el catálogo
 * desde la aplicación. El seeder no pisa nombres ni estados ya modificados.
 */
final class ConsentimientoTipoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $tipo) {
            ConsentimientoTipo::query()->firstOrCreate(
                ['codigo' => $tipo['codigo']],
                [
                    'nombre' => $tipo['nombre'],
                    'descripcion' => $tipo['descripcion'],
                    'orden' => $tipo['orden'],
                    'activo' => true,
                    'version' => 1,
                ],
            );
        }
    }

    /**
     * @return list<array{codigo: string, nombre: string, descripcion: string, orden: int}>
     */
    private function catalog(): array
    {
        return [
            [
                'codigo' => 'atencion',
                'nombre' => 'Consentimiento para la atención',
                'descripcion' => 'Autorización de la familia para la atención del niño o niña en el centro.',
                'orden' => 10,
            ],
            [
                'codigo' => 'uso_datos_contacto',
                'nombre' => 'Uso de datos de contacto',
                'descripcion' => 'Autorización para usar los datos de contacto de la familia para comunicaciones del centro.',
                'orden' => 20,
            ],
            [
                'codigo' => 'uso_imagen',
                'nombre' => 'Uso de imágenes',
                'descripcion' => 'Autorización para el uso de imágenes del niño o niña en actividades y materiales del centro.',
                'orden' => 30,
            ],
        ];
    }
}
