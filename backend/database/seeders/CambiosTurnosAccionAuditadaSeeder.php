<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * [Reprogramación y Cancelación] RF-010 / RNF-012.
 */
final class CambiosTurnosAccionAuditadaSeeder extends Seeder
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
            ['entidad' => 'turno', 'accion' => 'REPROGRAMAR', 'critico' => false, 'descripcion' => 'Reprogramación de un turno'],
            ['entidad' => 'turno', 'accion' => 'CANCELAR', 'critico' => true, 'descripcion' => 'Cancelación individual de un turno'],
            ['entidad' => 'turno', 'accion' => 'CANCELAR_MASIVO', 'critico' => true, 'descripcion' => 'Cancelación de un turno dentro de un lote masivo'],
            ['entidad' => 'turno', 'accion' => 'CREAR_POR_REPROGRAMACION', 'critico' => false, 'descripcion' => 'Turno creado por reprogramación'],
            ['entidad' => 'turno_cancelacion_masiva', 'accion' => 'CANCELAR_MASIVO', 'critico' => true, 'descripcion' => 'Lote de cancelación masiva de turnos'],
        ];
    }
}
