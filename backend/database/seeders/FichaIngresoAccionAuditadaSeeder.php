<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas de la ficha de ingreso (04.03/RF-011).
 */
final class FichaIngresoAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => 'pacientes',
                    'entidad' => 'ficha_ingreso',
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
            ['accion' => 'CREAR', 'critico' => false, 'descripcion' => 'Alta de ficha de ingreso'],
            ['accion' => 'ACTUALIZAR', 'critico' => false, 'descripcion' => 'Edición de ficha de ingreso'],
            ['accion' => 'COMPLETAR', 'critico' => false, 'descripcion' => 'Ficha marcada como completa'],
            ['accion' => 'ARCHIVAR', 'critico' => true, 'descripcion' => 'Archivo de ficha de ingreso'],
            ['accion' => 'REACTIVAR', 'critico' => false, 'descripcion' => 'Reactivación de ficha de ingreso'],
            ['accion' => 'CONSULTAR', 'critico' => false, 'descripcion' => 'Consulta de ficha de ingreso o revisiones'],
            ['accion' => 'EXPORTAR', 'critico' => false, 'descripcion' => 'Exportación de ficha de ingreso'],
        ];
    }
}
