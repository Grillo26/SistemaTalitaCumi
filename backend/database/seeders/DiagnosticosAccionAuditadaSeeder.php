<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Support\DiagnosticoAuditAction;
use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas del diagnóstico con varios profesionales (07.05).
 */
final class DiagnosticosAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => 'agenda',
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
            [
                'entidad' => 'turno',
                'accion' => DiagnosticoAuditAction::PROGRAMAR,
                'critico' => false,
                'descripcion' => 'Programación de turno de diagnóstico',
            ],
            [
                'entidad' => 'atencion_diagnostico',
                'accion' => DiagnosticoAuditAction::ACTUALIZAR_ESTADO,
                'critico' => true,
                'descripcion' => 'Cambio de estado general del diagnóstico',
            ],
            [
                'entidad' => 'atencion_diagnostico',
                'accion' => DiagnosticoAuditAction::CANCELAR,
                'critico' => true,
                'descripcion' => 'Cancelación del diagnóstico',
            ],
        ];
    }
}
