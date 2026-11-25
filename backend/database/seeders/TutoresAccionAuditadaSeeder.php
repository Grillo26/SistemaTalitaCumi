<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas del módulo 04.02 (04.02/RF-014).
 *
 * Seeder propio para no modificar el catálogo del módulo 02. Idempotente.
 */
final class TutoresAccionAuditadaSeeder extends Seeder
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
            ['entidad' => 'tutor', 'accion' => 'CREAR', 'critico' => false, 'descripcion' => 'Alta de tutor'],
            ['entidad' => 'tutor', 'accion' => 'ACTUALIZAR', 'critico' => false, 'descripcion' => 'Edición de tutor'],
            ['entidad' => 'tutor', 'accion' => 'CAMBIAR_ESTADO', 'critico' => true, 'descripcion' => 'Activación o desactivación de tutor'],
            ['entidad' => 'tutor', 'accion' => 'CONSULTAR', 'critico' => false, 'descripcion' => 'Consulta de la ficha del tutor'],
            ['entidad' => 'paciente_tutor', 'accion' => 'ASOCIAR', 'critico' => false, 'descripcion' => 'Asociación de tutor a paciente'],
            ['entidad' => 'paciente_tutor', 'accion' => 'DESASOCIAR', 'critico' => false, 'descripcion' => 'Desasociación de tutor'],
            ['entidad' => 'paciente_tutor', 'accion' => 'CAMBIAR_PRINCIPAL', 'critico' => false, 'descripcion' => 'Cambio de tutor principal'],
        ];
    }
}
