<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas del submódulo 03 (RF-D10).
 */
final class CatalogoDiagnosticosAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['accion' => 'CREAR', 'critico' => false, 'descripcion' => 'Creación de tipo de diagnóstico'],
            ['accion' => 'ACTUALIZAR', 'critico' => false, 'descripcion' => 'Actualización de tipo y requisitos'],
            ['accion' => 'ACTIVAR', 'critico' => false, 'descripcion' => 'Activación de tipo de diagnóstico'],
            ['accion' => 'DESACTIVAR', 'critico' => true, 'descripcion' => 'Desactivación de tipo de diagnóstico'],
        ] as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => 'catalogo_servicios',
                    'entidad' => 'tipo_diagnostico',
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
