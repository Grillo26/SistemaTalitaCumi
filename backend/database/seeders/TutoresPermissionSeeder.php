<?php

namespace Database\Seeders;

use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del módulo 04.02 — Registro de tutores y contactos.
 *
 * La matriz de tutores es independiente de 04.01. La primera vez alinea los
 * permisos existentes que el núcleo había sembrado bajo 'pacientes' y deja
 * la marca 'pacientes.tutores'. Después, una re-siembra no revierte cambios
 * hechos por el Superadmin desde la UI.
 */
final class TutoresPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PacientesPermission::tutorAll() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $initialized = DB::table('modulo_permisos_inicializado')
            ->where('modulo', 'pacientes.tutores')
            ->exists();

        if (! $initialized) {
            $superadmin = Role::findByName('superadmin', 'web');
            $superadmin->givePermissionTo(PacientesPermission::tutorAll());

            $administracion = Role::findByName('administracion', 'web');
            $administracion->revokePermissionTo([
                PacientesPermission::TUTOR_CREATE,
                PacientesPermission::TUTOR_EDIT,
                PacientesPermission::TUTOR_ASSOCIATE,
                PacientesPermission::TUTOR_CHANGE_STATUS,
            ]);
            $administracion->givePermissionTo([
                PacientesPermission::TUTOR_VIEW,
                PacientesPermission::TUTOR_EDIT_CONTACT,
                PacientesPermission::TUTOR_VIEW_CONTACT,
                PacientesPermission::TUTOR_EXPORT,
            ]);

            $recepcion = Role::findByName('recepcion', 'web');
            $recepcion->revokePermissionTo([
                PacientesPermission::TUTOR_CHANGE_STATUS,
                PacientesPermission::TUTOR_EXPORT,
            ]);
            $recepcion->givePermissionTo([
                PacientesPermission::TUTOR_VIEW,
                PacientesPermission::TUTOR_CREATE,
                PacientesPermission::TUTOR_EDIT,
                PacientesPermission::TUTOR_VIEW_CONTACT,
                PacientesPermission::TUTOR_ASSOCIATE,
            ]);

            DB::table('modulo_permisos_inicializado')->insertOrIgnore([
                'modulo' => 'pacientes.tutores',
                'inicializado_en' => now(),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
