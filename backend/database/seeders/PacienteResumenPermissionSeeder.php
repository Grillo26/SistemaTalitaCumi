<?php

namespace Database\Seeders;

use App\Modules\Pacientes\Support\PacienteResumenPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del submódulo 04.04 (búsqueda y ficha resumen).
 *
 * La exportación del listado queda solo para Superadmin y Administración;
 * Recepción no exporta (04.04/RF-013). No se modifica `paciente.exportar`,
 * que sigue reservado para la ficha completa de 04.01.
 */
final class PacienteResumenPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PacienteResumenPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        if (InitialPermissionMatrix::shouldAssign('pacientes.resumen', PacienteResumenPermission::all())) {
            Role::findByName('superadmin', 'web')
                ->givePermissionTo(PacienteResumenPermission::all());

            Role::findByName('administracion', 'web')
                ->givePermissionTo(PacienteResumenPermission::all());

            InitialPermissionMatrix::mark('pacientes.resumen');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
