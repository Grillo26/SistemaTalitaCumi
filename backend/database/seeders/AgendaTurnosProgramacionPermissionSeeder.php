<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Support\AgendaTurnosPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * [Programación de Turnos] RF-007 / RF-013 / RF-016.
 *
 * Recepción genera y asigna profesional en su sucursal; Administración
 * además regenera y gestiona feriados. La decisión siempre es por permiso,
 * nunca por nombre de rol.
 */
final class AgendaTurnosProgramacionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AgendaTurnosPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'agenda.programacion_turnos',
            AgendaTurnosPermission::programacion(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(AgendaTurnosPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo(AgendaTurnosPermission::all());

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                AgendaTurnosPermission::VER,
                AgendaTurnosPermission::GENERAR,
                AgendaTurnosPermission::ASIGNAR_PROFESIONAL,
            ]);

            InitialPermissionMatrix::mark('agenda.programacion_turnos');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
