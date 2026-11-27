<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas de inscripción a salas grupales (RNF-012).
 */
final class InscripcionesAtencionesAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => 'inscripciones',
                    'entidad' => 'inscripcion_sala',
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
            ['accion' => 'CREAR', 'critico' => false, 'descripcion' => 'Alta de inscripción'],
            ['accion' => 'ACTUALIZAR', 'critico' => false, 'descripcion' => 'Modificación de inscripción'],
            ['accion' => 'RENOVAR', 'critico' => false, 'descripcion' => 'Renovación de inscripción'],
            ['accion' => 'RETIRAR', 'critico' => true, 'descripcion' => 'Baja de inscripción'],
        ];
    }
}
