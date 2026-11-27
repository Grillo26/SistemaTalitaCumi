<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Support\AgendaTurnosPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * [Agenda Semanal] RF-011, RF-016.
 *
 * Recepción y Administración consultan la agenda de sus sucursales.
 * Administración además exporta. La decisión siempre es por permiso.
 */
final class AgendaTurnosPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AgendaTurnosPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'agenda.agenda_semanal',
            AgendaTurnosPermission::all(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(AgendaTurnosPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo(AgendaTurnosPermission::all());

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                AgendaTurnosPermission::VER,
            ]);

            InitialPermissionMatrix::mark('agenda.agenda_semanal');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
