<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas del módulo 03 (03/RF-D11, 03/RF-E10 / 03/RN-17).
 *
 * Seeder propio para no modificar el catálogo del módulo 02. Idempotente.
 */
final class ProfesionalesAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => 'profesionales',
                    'entidad' => $item['entidad'],
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
     * @return list<array{entidad: string, accion: string, critico: bool, descripcion: string}>
     */
    private function catalog(): array
    {
        $items = [];

        foreach (['profesional', 'especialidad'] as $entity) {
            $items[] = ['entidad' => $entity, 'accion' => 'CREAR', 'critico' => false, 'descripcion' => 'Creación'];
            $items[] = ['entidad' => $entity, 'accion' => 'ACTUALIZAR', 'critico' => false, 'descripcion' => 'Actualización'];
            $items[] = ['entidad' => $entity, 'accion' => 'ACTIVAR', 'critico' => false, 'descripcion' => 'Activación'];
            $items[] = ['entidad' => $entity, 'accion' => 'DESACTIVAR', 'critico' => true, 'descripcion' => 'Desactivación'];
        }

        $items[] = [
            'entidad' => 'profesional_especialidad',
            'accion' => 'ACTUALIZAR',
            'critico' => false,
            'descripcion' => 'Cambio de especialidades del profesional',
        ];

        foreach (['CREAR', 'ACTIVAR', 'DESACTIVAR'] as $action) {
            $items[] = [
                'entidad' => 'profesional_sucursal',
                'accion' => $action,
                'critico' => $action === 'DESACTIVAR',
                'descripcion' => match ($action) {
                    'CREAR' => 'Asignación de sucursal de trabajo',
                    'ACTIVAR' => 'Activación de sucursal de trabajo',
                    'DESACTIVAR' => 'Desactivación de sucursal de trabajo',
                },
            ];
        }

        return $items;
    }
}
