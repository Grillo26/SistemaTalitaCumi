<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acción auditada del lote de renovación múltiple.
 */
final class VigenciasAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        AccionAuditada::query()->firstOrCreate(
            [
                'modulo' => 'inscripciones',
                'entidad' => 'renovacion_multiple',
                'accion' => 'RENOVAR',
            ],
            [
                'critico' => false,
                'activo' => true,
                'descripcion' => 'Renovación múltiple de inscripciones y paquetes',
            ],
        );
    }
}
