<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Enums\AccionCambioTurno;
use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * 07.04/RF-011, 07.04/RNF-012.
 *
 * Acciones del catálogo de auditoría. El detalle de corrección fuera de
 * ventana se marca como crítico en la fila de bitácora, no en este catálogo.
 */
final class AsistenciaTurnosAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => 'agenda',
                    'entidad' => 'turno',
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
            [
                'accion' => AccionCambioTurno::ASISTIO->value,
                'critico' => false,
                'descripcion' => 'Registro o corrección de un turno como asistió',
            ],
            [
                'accion' => AccionCambioTurno::FALTO->value,
                'critico' => false,
                'descripcion' => 'Registro o corrección de un turno como faltó',
            ],
        ];
    }
}
