<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas de contactos de emergencia (04.02/RF-012, RF-014).
 *
 * Seeder propio de la Fase B. Idempotente.
 */
final class ContactosEmergenciaAccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => 'pacientes',
                    'entidad' => 'contacto_emergencia',
                    'accion' => $item['accion'],
                ],
                [
                    'critico' => false,
                    'activo' => true,
                    'descripcion' => $item['descripcion'],
                ],
            );
        }
    }

    /**
     * @return list<array{accion: string, descripcion: string}>
     */
    private function catalog(): array
    {
        return [
            ['accion' => 'CREAR', 'descripcion' => 'Alta de contacto de emergencia'],
            ['accion' => 'ACTUALIZAR', 'descripcion' => 'Edición de contacto de emergencia'],
            ['accion' => 'ELIMINAR', 'descripcion' => 'Eliminación de contacto de emergencia'],
        ];
    }
}
