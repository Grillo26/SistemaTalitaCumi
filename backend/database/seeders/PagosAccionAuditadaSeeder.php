<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas de pagos (08.01/RF-015). El registro con fecha futura
 * autorizada se marca `critico` por fila en la bitácora.
 */
final class PagosAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        AccionAuditada::query()->firstOrCreate(
            [
                'modulo' => 'pagos',
                'entidad' => 'pago',
                'accion' => 'CREAR',
            ],
            [
                'critico' => false,
                'activo' => true,
                'descripcion' => 'Registro de pago (fecha futura autorizada: crítico)',
            ],
        );
    }
}
