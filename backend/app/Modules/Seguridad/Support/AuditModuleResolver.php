<?php

namespace App\Modules\Seguridad\Support;

use App\Modules\Seguridad\Enums\AuditResult;

/**
 * Resolución de módulos, entidades y etiquetas de auditoría (02/RF-B03).
 */
final class AuditModuleResolver
{
    private const ENTITY_MODULE = [
        'sucursal' => 'organizacion',
        'tipo_sala' => 'organizacion',
        'turno_catalogo' => 'organizacion',
        'sala' => 'organizacion',
        'ambiente' => 'organizacion',
        'area' => 'catalogo_servicios',
        'servicio' => 'catalogo_servicios',
        'tipo_servicio' => 'catalogo_servicios',
        'categoria_tarifa' => 'catalogo_servicios',
        'tarifa' => 'catalogo_servicios',
        'tipo_diagnostico' => 'catalogo_servicios',
        'parametro' => 'organizacion',
        'usuario' => 'usuarios',
        'sesion' => 'autenticacion',
        'rol' => 'roles',
        'matriz_permisos' => 'roles',
        'bitacora' => 'auditoria',
        'exportacion' => 'auditoria',
        'paciente' => 'pacientes',
        'tutor' => 'pacientes',
        'ficha_ingreso' => 'pacientes',
        'informe_entrevista' => 'clinico',
        'resultado_diagnostico' => 'clinico',
        'derivacion' => 'clinico',
        'pago' => 'pagos',
        'caja' => 'pagos',
        'cuota' => 'pagos',
        'inscripcion_sala' => 'inscripciones',
        'paquete_sesiones' => 'inscripciones',
        'turno' => 'agenda',
    ];

    private const MODULE_LABELS = [
        'organizacion' => 'Organización',
        'catalogo_servicios' => 'Catálogo de servicios',
        'usuarios' => 'Usuarios',
        'autenticacion' => 'Autenticación',
        'roles' => 'Roles y permisos',
        'auditoria' => 'Auditoría',
        'pacientes' => 'Pacientes',
        'clinico' => 'Clínico',
        'pagos' => 'Pagos y caja',
        'inscripciones' => 'Inscripciones',
        'agenda' => 'Agenda',
        'otros' => 'Otros',
    ];

    private const ENTITY_LABELS = [
        'sucursal' => 'Sucursal',
        'tipo_sala' => 'Tipo de sala',
        'turno_catalogo' => 'Turno operativo',
        'sala' => 'Sala',
        'ambiente' => 'Ambiente',
        'area' => 'Área',
        'servicio' => 'Servicio',
        'tipo_servicio' => 'Tipo de servicio',
        'categoria_tarifa' => 'Categoría de tarifa',
        'tarifa' => 'Tarifa',
        'tipo_diagnostico' => 'Tipo de diagnóstico',
        'parametro' => 'Parámetro',
        'usuario' => 'Usuario',
        'sesion' => 'Sesión',
        'rol' => 'Rol',
        'matriz_permisos' => 'Matriz de permisos',
        'bitacora' => 'Bitácora',
        'exportacion' => 'Exportación',
        'paciente' => 'Paciente',
        'tutor' => 'Tutor',
        'ficha_ingreso' => 'Ficha de ingreso',
        'informe_entrevista' => 'Informe de entrevista',
        'resultado_diagnostico' => 'Resultado de diagnóstico',
        'derivacion' => 'Derivación',
        'pago' => 'Pago',
        'caja' => 'Caja',
        'cuota' => 'Cuota',
        'inscripcion_sala' => 'Inscripción a sala',
        'paquete_sesiones' => 'Paquete de sesiones',
        'turno' => 'Turno',
    ];

    private const ACTION_LABELS = [
        'CREAR' => 'Creación',
        'EDITAR' => 'Edición',
        'ACTUALIZAR' => 'Actualización',
        'ACTIVAR' => 'Activación',
        'DESACTIVAR' => 'Desactivación',
        'RESETEAR_PASSWORD' => 'Reseteo de contraseña',
        'DESBLOQUEAR' => 'Desbloqueo',
        'BLOQUEAR' => 'Bloqueo',
        'CERRAR_SESIONES' => 'Cierre de sesiones',
        'CAMBIAR_PASSWORD' => 'Cambio de contraseña',
        'INICIAR_SESION' => 'Inicio de sesión',
        'LOGIN_FALLIDO' => 'Intento fallido',
        'CERRAR_SESION' => 'Cierre de sesión',
        'SESION_EXPIRADA' => 'Sesión expirada',
        'SESION_REEMPLAZADA' => 'Sesión reemplazada',
        'EDITAR_PERMISOS' => 'Edición de permisos',
        'CONSULTAR' => 'Consulta',
        'EXPORTAR' => 'Exportación',
        'RESTAURAR' => 'Restauración',
        'CAMBIAR_COLOR' => 'Cambio de color',
        'CERRAR_VIGENCIA' => 'Cierre de vigencia',
    ];

    public static function fromEntity(?string $entity): string
    {
        if ($entity === null || $entity === '') {
            return 'otros';
        }

        return self::ENTITY_MODULE[$entity] ?? 'otros';
    }

    /**
     * @return list<string>
     */
    public static function entitiesFor(string $module): array
    {
        return array_keys(array_filter(
            self::ENTITY_MODULE,
            static fn (string $value): bool => $value === $module,
        ));
    }

    public static function moduleLabel(?string $module): string
    {
        return self::MODULE_LABELS[$module ?? ''] ?? ucfirst((string) $module);
    }

    public static function entityLabel(?string $entity): string
    {
        if ($entity === null || $entity === '') {
            return '—';
        }

        return self::ENTITY_LABELS[$entity] ?? ucfirst(str_replace('_', ' ', $entity));
    }

    public static function actionLabel(?string $action): string
    {
        if ($action === null || $action === '') {
            return '—';
        }

        return self::ACTION_LABELS[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }

    public static function resultLabel(?string $result): string
    {
        return AuditResult::tryFrom($result ?? '')?->label() ?? '—';
    }
}
