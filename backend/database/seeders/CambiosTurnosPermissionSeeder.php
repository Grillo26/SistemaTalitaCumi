<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Support\CambiosTurnosPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * [Reprogramación y Cancelación] RF-013 / RF-012.
 *
 * Recepción reprograma, cancela y consulta historial en su sucursal;
 * Administración además cancela masivamente, ve el reporte y exporta.
 */
final class CambiosTurnosPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([...CambiosTurnosPermission::all(), 'turno.ver'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'agenda.cambios_turnos',
            CambiosTurnosPermission::changes(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo([...CambiosTurnosPermission::all(), 'turno.ver']);

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([...CambiosTurnosPermission::all(), 'turno.ver']);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                'turno.ver',
                CambiosTurnosPermission::REPROGRAMAR,
                CambiosTurnosPermission::CANCELAR,
                CambiosTurnosPermission::HISTORIAL,
            ]);

            InitialPermissionMatrix::mark('agenda.cambios_turnos');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
