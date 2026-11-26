<?php

namespace App\Modules\Seguridad\Support;

use App\Modules\Organizacion\Support\BranchPermission;

/**
 * Catálogo de presentación de permisos (02/RF-R02, 02/RF-R03).
 *
 * El módulo se deriva del prefijo `entidad.accion`. No decide permisos:
 * solo agrupa y etiqueta para la grilla de la UI.
 */
final class PermissionCatalog
{
    private const MODULE_LABELS = [
        'sucursal' => 'Sucursales',
        'catalogo' => 'Catálogos de salas',
        'tipo_sala' => 'Tipos de sala',
        'turno' => 'Turnos',
        'sala' => 'Salas',
        'ambiente' => 'Ambientes',
        'areas' => 'Áreas',
        'servicios' => 'Servicios',
        'parametro' => 'Configuración',
        'usuario' => 'Usuarios',
        'rol' => 'Roles',
        'matriz' => 'Matriz de permisos',
        'bitacora' => 'Bitácora',
        'paciente' => 'Pacientes',
        'tutor' => 'Tutores',
        'inscripcion' => 'Inscripciones',
        'pago' => 'Pagos',
        'caja' => 'Caja',
        'reporte' => 'Reportes',
        'clinico' => 'Clínico',
    ];

    private const MODULE_ORDER = [
        'sucursal' => 10,
        'catalogo' => 20,
        'tipo_sala' => 21,
        'turno' => 22,
        'sala' => 23,
        'ambiente' => 24,
        'areas' => 25,
        'servicios' => 26,
        'parametro' => 30,
        'usuario' => 40,
        'rol' => 41,
        'matriz' => 42,
        'bitacora' => 43,
    ];

    private const ACTION_LABELS = [
        'ver' => 'Ver',
        'crear' => 'Crear',
        'editar' => 'Editar',
        'editar_contacto' => 'Editar contacto',
        'editar_estructura' => 'Editar estructura',
        'editar_datos' => 'Editar datos',
        'cambiar_estado' => 'Cambiar estado',
        'administrar' => 'Administrar',
        'ver_operativos' => 'Ver operativos',
        'restaurar' => 'Restaurar',
        'exportar' => 'Exportar',
        'alcance_global' => 'Alcance global',
        'resetear_password' => 'Resetear contraseña',
        'desbloquear' => 'Desbloquear',
        'cerrar_sesiones' => 'Cerrar sesiones',
        'editar_permisos' => 'Editar permisos',
    ];

    private const ACTION_ORDER = [
        'ver' => 10,
        'crear' => 20,
        'editar' => 30,
        'editar_contacto' => 31,
        'editar_estructura' => 32,
        'editar_datos' => 33,
        'cambiar_estado' => 40,
        'administrar' => 50,
        'ver_operativos' => 60,
        'restaurar' => 70,
        'exportar' => 80,
        'alcance_global' => 90,
        'resetear_password' => 100,
        'desbloquear' => 110,
        'cerrar_sesiones' => 120,
        'editar_permisos' => 130,
    ];

    public static function moduleKey(string $permissionName): string
    {
        return explode('.', $permissionName, 2)[0];
    }

    public static function actionKey(string $permissionName): string
    {
        $parts = explode('.', $permissionName, 2);

        return $parts[1] ?? $permissionName;
    }

    public static function moduleLabel(string $module): string
    {
        return self::MODULE_LABELS[$module] ?? ucfirst($module);
    }

    public static function moduleOrder(string $module): int
    {
        return self::MODULE_ORDER[$module] ?? 500;
    }

    public static function actionLabel(string $action): string
    {
        return self::ACTION_LABELS[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }

    public static function actionOrder(string $action): int
    {
        return self::ACTION_ORDER[$action] ?? 500;
    }

    /**
     * @param  list<string>  $permissionNames
     * @return list<string>
     */
    public static function critical(): array
    {
        return array_values(array_unique([
            ...SecurityPermission::critical(),
            BranchPermission::GLOBAL_SCOPE,
        ]));
    }
}
