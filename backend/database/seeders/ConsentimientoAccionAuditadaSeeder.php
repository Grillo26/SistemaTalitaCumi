<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Acciones auditadas de consentimientos (04.03 Fase B).
 */
final class ConsentimientoAccionAuditadaSeeder extends Seeder
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
            ['entidad' => 'consentimiento', 'accion' => 'OTORGAR', 'critico' => false, 'descripcion' => 'Registro de consentimiento otorgado'],
            ['entidad' => 'consentimiento', 'accion' => 'NEGAR', 'critico' => false, 'descripcion' => 'Registro de negativa de consentimiento'],
            ['entidad' => 'consentimiento', 'accion' => 'REVOCAR', 'critico' => true, 'descripcion' => 'Revocación de consentimiento'],
            ['entidad' => 'consentimiento', 'accion' => 'CONSULTAR', 'critico' => false, 'descripcion' => 'Consulta de consentimientos'],
            ['entidad' => 'consentimiento_tipo', 'accion' => 'CREAR', 'critico' => false, 'descripcion' => 'Alta de tipo de consentimiento'],
            ['entidad' => 'consentimiento_tipo', 'accion' => 'ACTUALIZAR', 'critico' => false, 'descripcion' => 'Edición de tipo de consentimiento'],
        ];
    }
}
