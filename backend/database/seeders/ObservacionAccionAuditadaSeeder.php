<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas de observaciones fechadas (04.03 Fase C).
 */
final class ObservacionAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        AccionAuditada::query()->firstOrCreate(
            [
                'modulo' => 'pacientes',
                'entidad' => 'ficha_ingreso_observacion',
                'accion' => 'CREAR',
            ],
            [
                'critico' => false,
                'activo' => true,
                'descripcion' => 'Alta de observación fechada de ficha de ingreso',
            ],
        );
    }
}
