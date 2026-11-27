<?php

namespace Database\Seeders;

use App\Modules\InscripcionesAtenciones\Support\EstadoPermission;
use Database\Seeders\Support\InitialPermissionMatrix;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * [Estados Paciente e Inscripción] RF-013 / RN-005.
 *
 * Recepción consulta estados de paciente y pausa/reactiva inscripciones de
 * su sucursal. Administración ejecuta todos los cambios; los retiros de
 * paciente quedan en paciente.retirar (seeder de Pacientes) y los de
 * inscripción en los permisos existentes de Salas/Paquetes.
 */
final class EstadosPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (EstadoPermission::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $assign = InitialPermissionMatrix::shouldAssign(
            'inscripciones.estados',
            EstadoPermission::all(),
        );

        if ($assign) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(EstadoPermission::all());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->givePermissionTo([
                EstadoPermission::VIEW,
                EstadoPermission::REPORTS,
                EstadoPermission::EXPORT,
                EstadoPermission::INSCRIPTION_PAUSE,
                EstadoPermission::INSCRIPTION_REACTIVATE,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->givePermissionTo([
                EstadoPermission::VIEW,
                EstadoPermission::INSCRIPTION_PAUSE,
                EstadoPermission::INSCRIPTION_REACTIVATE,
            ]);

            InitialPermissionMatrix::mark('inscripciones.estados');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
