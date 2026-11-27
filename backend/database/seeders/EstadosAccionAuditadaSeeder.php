<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * [Estados Paciente e Inscripción] RF-009 / RNF-011.
 */
final class EstadosAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['inscripcion_sala', 'paquete_sesiones'] as $entidad) {
            foreach ([
                ['accion' => 'PAUSAR', 'critico' => false, 'descripcion' => 'Pausa de inscripción'],
                ['accion' => 'REACTIVAR', 'critico' => false, 'descripcion' => 'Reactivación de inscripción'],
            ] as $item) {
                AccionAuditada::query()->firstOrCreate(
                    [
                        'modulo' => 'inscripciones',
                        'entidad' => $entidad,
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

        AccionAuditada::query()->firstOrCreate(
            [
                'modulo' => 'pacientes',
                'entidad' => 'paciente',
                'accion' => 'RETIRAR_PACIENTE',
            ],
            [
                'critico' => true,
                'activo' => true,
                'descripcion' => 'Retiro del paciente con cascada de inscripciones',
            ],
        );
    }
}
