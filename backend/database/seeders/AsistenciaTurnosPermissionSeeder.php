<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Support\AsistenciaTurnosPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * 07.04/RF-010, 07.04/RF-012, 07.04/RN-007.
 *
 * Recepción registra y corrige dentro de ventana; Administración además
 * corrige fuera de ventana y exporta. La decisión es por permiso, nunca por
 * nombre de rol.
 */
final class AsistenciaTurnosPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AsistenciaTurnosPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'agenda.asistencia_turnos',
            AsistenciaTurnosPermission::changes(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(AsistenciaTurnosPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo(AsistenciaTurnosPermission::all());

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                AsistenciaTurnosPermission::VER,
                AsistenciaTurnosPermission::REGISTRAR,
                AsistenciaTurnosPermission::VER_INDICADORES,
                AsistenciaTurnosPermission::RESUMEN_DIARIO,
            ]);

            InitialPermissionMatrix::mark('agenda.asistencia_turnos');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
