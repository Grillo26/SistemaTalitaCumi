<?php

namespace App\Modules\Pacientes\Support;

/**
 * Códigos de permiso del módulo 04.
 *
 * La aplicación decide por permiso, nunca por nombre de rol. La matriz
 * inicial es la única capa que nombra roles.
 */
final class PacientesPermission
{
    public const PATIENT_VIEW = 'paciente.ver';

    public const PATIENT_CREATE = 'paciente.crear';

    public const PATIENT_EDIT = 'paciente.editar';

    public const PATIENT_CHANGE_STATUS = 'paciente.cambiar_estado';

    /** Retiro definitivo del paciente (06.05/RN-005). */
    public const PATIENT_RETIRE = 'paciente.retirar';

    /** Alergias, condiciones médicas y edición de esos campos. */
    public const PATIENT_VIEW_SENSITIVE = 'paciente.ver_sensibles';

    /** Reservado para 04.01/RF-015; la exportación aún no está implementada. */
    public const PATIENT_EXPORT = 'paciente.exportar';

    public const TUTOR_VIEW = 'tutor.ver';

    public const TUTOR_CREATE = 'tutor.crear';

    /** Edición completa: identidad y contacto. */
    public const TUTOR_EDIT = 'tutor.editar';

    /** Edición de teléfonos, email y dirección. */
    public const TUTOR_EDIT_CONTACT = 'tutor.editar_contacto';

    /** Ver teléfonos, email y dirección completos. */
    public const TUTOR_VIEW_CONTACT = 'tutor.ver_contacto';

    /** Asociar, desasociar y designar tutor principal. */
    public const TUTOR_ASSOCIATE = 'tutor.asociar';

    public const TUTOR_CHANGE_STATUS = 'tutor.cambiar_estado';

    /** Reservado para 04.02/RF-016. */
    public const TUTOR_EXPORT = 'tutor.exportar';

    /** Ver los contactos de emergencia y su teléfono completo. */
    public const CONTACT_EMERGENCY_VIEW = 'paciente.ver_contacto_emergencia';

    /** Crear, editar y eliminar contactos de emergencia. */
    public const CONTACT_EMERGENCY_EDIT = 'paciente.editar_contacto_emergencia';

    /**
     * Permiso transversal del módulo 01; se reutiliza sin duplicarlo.
     */
    public const GLOBAL_SCOPE = 'sucursal.alcance_global';

    /**
     * Permisos de 04.01.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PATIENT_VIEW,
            self::PATIENT_CREATE,
            self::PATIENT_EDIT,
            self::PATIENT_CHANGE_STATUS,
            self::PATIENT_RETIRE,
            self::PATIENT_VIEW_SENSITIVE,
            self::PATIENT_EXPORT,
        ];
    }

    /**
     * Permisos de 04.02.
     *
     * @return list<string>
     */
    public static function tutorAll(): array
    {
        return [
            self::TUTOR_VIEW,
            self::TUTOR_CREATE,
            self::TUTOR_EDIT,
            self::TUTOR_EDIT_CONTACT,
            self::TUTOR_VIEW_CONTACT,
            self::TUTOR_ASSOCIATE,
            self::TUTOR_CHANGE_STATUS,
            self::TUTOR_EXPORT,
        ];
    }

    /**
     * Permisos de contactos de emergencia (04.02/RF-012).
     *
     * @return list<string>
     */
    public static function contactoEmergenciaAll(): array
    {
        return [
            self::CONTACT_EMERGENCY_VIEW,
            self::CONTACT_EMERGENCY_EDIT,
        ];
    }
}
