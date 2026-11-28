<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas de programación de turnos (RF-012 / RNF-012).
 */
final class AgendaTurnosAccionAuditadaSeeder extends Seeder
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
                'entidad' => 'turno_lote_generacion',
                'accion' => 'GENERAR',
                'critico' => false,
                'descripcion' => 'Generación de turnos desde una entidad origen',
            ],
            [
                'entidad' => 'turno_lote_generacion',
                'accion' => 'REGENERAR',
                'critico' => true,
                'descripcion' => 'Regeneración de turnos con cancelación de futuros programados',
            ],
            [
                'entidad' => 'turno',
                'accion' => 'ASIGNAR_PROFESIONAL',
                'critico' => false,
                'descripcion' => 'Asignación de profesional a un lote o subconjunto de turnos',
            ],
            [
                'entidad' => 'feriado',
                'accion' => 'CREAR_FERIADO',
                'critico' => false,
                'descripcion' => 'Alta de feriado',
            ],
            [
                'entidad' => 'feriado',
                'accion' => 'ACTUALIZAR_FERIADO',
                'critico' => false,
                'descripcion' => 'Actualización de feriado',
            ],
            [
                'entidad' => 'feriado',
                'accion' => 'DESACTIVAR_FERIADO',
                'critico' => false,
                'descripcion' => 'Desactivación de feriado',
            ],
        ];
    }
}
