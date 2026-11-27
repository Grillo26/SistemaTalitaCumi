<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas de paquetes de sesiones (RNF-012).
 */
final class PaquetesSesionesAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => 'inscripciones',
                    'entidad' => 'paquete_sesiones',
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

    /**
     * @return list<array{accion: string, critico: bool, descripcion: string}>
     */
    private function catalog(): array
    {
        return [
            ['accion' => 'CREAR', 'critico' => false, 'descripcion' => 'Alta de paquete de sesiones'],
            ['accion' => 'ACTUALIZAR', 'critico' => false, 'descripcion' => 'Modificación de paquete de sesiones'],
            ['accion' => 'RENOVAR', 'critico' => false, 'descripcion' => 'Renovación de paquete de sesiones'],
            ['accion' => 'RETIRAR', 'critico' => true, 'descripcion' => 'Baja de paquete de sesiones'],
        ];
    }
}
