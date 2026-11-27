<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas de entrevistas y diagnósticos.
 */
final class EntrevistaDiagnosticoAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['atencion_entrevista', 'atencion_diagnostico'] as $entidad) {
            foreach ($this->catalog() as $item) {
                AccionAuditada::query()->firstOrCreate(
                    [
                        'modulo' => 'inscripciones',
                        'entidad' => $entidad,
                        'accion' => $item['accion'],
                    ],
                    [
                        'critico' => $item['critico'],
                        'activo' => true,
                        'descripcion' => $item['descripcion'],
                    ],
                );
            }
        }
    }

    /**
     * @return list<array{accion: string, critico: bool, descripcion: string}>
     */
    private function catalog(): array
    {
        return [
            ['accion' => 'CREAR', 'critico' => false, 'descripcion' => 'Alta de atención'],
            ['accion' => 'ACTUALIZAR', 'critico' => false, 'descripcion' => 'Modificación de atención'],
            ['accion' => 'RETIRAR', 'critico' => true, 'descripcion' => 'Baja de atención'],
            ['accion' => 'VINCULAR', 'critico' => false, 'descripcion' => 'Vinculación de entrevista'],
        ];
    }
}
