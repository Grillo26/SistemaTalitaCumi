<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas del módulo 04.01 (04.01/RF-012, 04.01/RF-013).
 *
 * Las acciones de tutores viven en TutoresAccionAuditadaSeeder (04.02).
 * Seeder propio para no modificar el catálogo del módulo 02. Idempotente.
 */
final class PacientesAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => 'pacientes',
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
        return [
            ['entidad' => 'paciente', 'accion' => 'CREAR', 'critico' => false, 'descripcion' => 'Alta de paciente'],
            ['entidad' => 'paciente', 'accion' => 'ACTUALIZAR', 'critico' => false, 'descripcion' => 'Edición de datos del paciente'],
            ['entidad' => 'paciente', 'accion' => 'CAMBIAR_ESTADO', 'critico' => true, 'descripcion' => 'Cambio de estado del paciente'],
            ['entidad' => 'paciente', 'accion' => 'CONSULTAR', 'critico' => false, 'descripcion' => 'Consulta de la ficha del paciente'],
            ['entidad' => 'paciente', 'accion' => 'CONFIRMAR_DUPLICADO', 'critico' => false, 'descripcion' => 'Confirmación de posible duplicado'],
        ];
    }
}
