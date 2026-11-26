<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas del submódulo 02 (RF-T15).
 */
final class CatalogoTarifasAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => 'catalogo_servicios',
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

        foreach (['categoria_tarifa', 'tarifa'] as $entity) {
            $items[] = ['entidad' => $entity, 'accion' => 'CREAR', 'critico' => false, 'descripcion' => 'Creación'];
            $items[] = ['entidad' => $entity, 'accion' => 'ACTUALIZAR', 'critico' => false, 'descripcion' => 'Actualización'];
            $items[] = ['entidad' => $entity, 'accion' => 'ACTIVAR', 'critico' => false, 'descripcion' => 'Activación'];
            $items[] = ['entidad' => $entity, 'accion' => 'DESACTIVAR', 'critico' => true, 'descripcion' => 'Desactivación'];
        }

        $items[] = [
            'entidad' => 'tarifa',
            'accion' => 'CERRAR_VIGENCIA',
            'critico' => false,
            'descripcion' => 'Cierre de vigencia de tarifa',
        ];

        return $items;
    }
}
