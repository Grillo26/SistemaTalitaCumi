<?php

namespace App\Modules\Profesionales\Support;

/**
 * Códigos de permiso del módulo 03.
 *
 * La aplicación decide por permiso, nunca por nombre de rol. La matriz
 * inicial es la única capa que nombra roles.
 */
final class ProfesionalesPermission
{
    public const PROFESSIONAL_VIEW = 'profesional.ver';

    public const PROFESSIONAL_CREATE = 'profesional.crear';

    /** Datos estructurales: nombre, CI, matrícula y especialidades. */
    public const PROFESSIONAL_EDIT_DATA = 'profesional.editar_datos';

    /** Contacto: teléfonos y correo. */
    public const PROFESSIONAL_EDIT_CONTACT = 'profesional.editar_contacto';

    public const PROFESSIONAL_CHANGE_STATUS = 'profesional.cambiar_estado';

    /** Reservado para 03/RF-D15; la exportación aún no está implementada. */
    public const PROFESSIONAL_EXPORT = 'profesional.exportar';

    public const PROFESSIONAL_VIEW_MATRIX = 'profesional.ver_matriz';

    public const PROFESSIONAL_BRANCH_ADMIN = 'profesional_sucursal.administrar';

    public const SPECIALTY_VIEW = 'especialidad.ver';

    public const SPECIALTY_ADMIN = 'especialidad.administrar';

    /** Reservados para cuando exista el módulo 07. */
    public const ASSIGNMENT_VIEW = 'asignacion.ver';

    public const ASSIGNMENT_CREATE = 'asignacion.crear';

    public const ASSIGNMENT_REASSIGN = 'asignacion.reasignar';

    public const ASSIGNMENT_UNASSIGN = 'asignacion.desasignar';

    public const ASSIGNMENT_EXPORT = 'asignacion.exportar';

    public const DIAGNOSTIC_ASSIGN = 'diagnostico.asignar_profesionales';

    /**
     * Permiso transversal del módulo 01; se reutiliza sin duplicarlo.
     */
    public const GLOBAL_SCOPE = 'sucursal.alcance_global';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PROFESSIONAL_VIEW,
            self::PROFESSIONAL_CREATE,
            self::PROFESSIONAL_EDIT_DATA,
            self::PROFESSIONAL_EDIT_CONTACT,
            self::PROFESSIONAL_CHANGE_STATUS,
            self::PROFESSIONAL_EXPORT,
            self::PROFESSIONAL_VIEW_MATRIX,
            self::PROFESSIONAL_BRANCH_ADMIN,
            self::SPECIALTY_VIEW,
            self::SPECIALTY_ADMIN,
            self::ASSIGNMENT_VIEW,
            self::ASSIGNMENT_CREATE,
            self::ASSIGNMENT_REASSIGN,
            self::ASSIGNMENT_UNASSIGN,
            self::ASSIGNMENT_EXPORT,
            self::DIAGNOSTIC_ASSIGN,
        ];
    }
}
