<?php

namespace Database\Seeders;

use App\Modules\Seguridad\Models\AccionAuditada;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de acciones auditadas (02/RF-B03).
 *
 * Idempotente: no duplica filas. Los módulos futuros agregan sus propias
 * acciones a este catálogo cuando implementen sus eventos.
 */
final class AccionAuditadaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $item) {
            AccionAuditada::query()->firstOrCreate(
                [
                    'modulo' => $item['modulo'],
                    'entidad' => $item['entidad'],
                    'accion' => $item['accion'],
                ],
                [
                    'critico' => $item['critico'],
                    'activo' => true,
                    'descripcion' => $item['descripcion'],
                ],
            );
        }
    }

    /**
     * @return list<array{modulo: string, entidad: string, accion: string, critico: bool, descripcion: string}>
     */
    private function catalog(): array
    {
        $items = [];

        $add = static function (
            string $modulo,
            string $entidad,
            string $accion,
            bool $critico,
            string $descripcion,
        ) use (&$items): void {
            $items[] = compact('modulo', 'entidad', 'accion', 'critico', 'descripcion');
        };

        foreach (['sucursal', 'tipo_sala', 'turno_catalogo', 'sala', 'ambiente'] as $entity) {
            $add('organizacion', $entity, 'CREAR', false, 'Creación');
            $add('organizacion', $entity, 'ACTUALIZAR', false, 'Actualización');
            $add('organizacion', $entity, 'ACTIVAR', false, 'Activación');
            $add('organizacion', $entity, 'DESACTIVAR', false, 'Desactivación');
        }

        $add('organizacion', 'parametro', 'CREAR', false, 'Creación de parámetro');
        $add('organizacion', 'parametro', 'EDITAR', false, 'Edición de parámetro');
        $add('organizacion', 'parametro', 'RESTAURAR', false, 'Restauración de parámetro');

        $add('usuarios', 'usuario', 'CREAR', false, 'Creación de usuario');
        $add('usuarios', 'usuario', 'EDITAR', false, 'Edición de usuario');
        $add('usuarios', 'usuario', 'ACTIVAR', false, 'Activación de usuario');
        $add('usuarios', 'usuario', 'DESACTIVAR', true, 'Desactivación de usuario');
        $add('usuarios', 'usuario', 'RESETEAR_PASSWORD', true, 'Reseteo de contraseña');
        $add('usuarios', 'usuario', 'DESBLOQUEAR', true, 'Desbloqueo de cuenta');
        $add('usuarios', 'usuario', 'BLOQUEAR', true, 'Bloqueo por intentos fallidos');
        $add('usuarios', 'usuario', 'CERRAR_SESIONES', true, 'Cierre remoto de sesiones');
        $add('usuarios', 'usuario', 'CAMBIAR_PASSWORD', false, 'Cambio de contraseña propio');

        $add('autenticacion', 'sesion', 'INICIAR_SESION', false, 'Inicio de sesión');
        $add('autenticacion', 'sesion', 'LOGIN_FALLIDO', false, 'Intento de inicio fallido');
        $add('autenticacion', 'sesion', 'CERRAR_SESION', false, 'Cierre de sesión');
        $add('autenticacion', 'sesion', 'SESION_EXPIRADA', false, 'Sesión expirada por inactividad');
        $add('autenticacion', 'sesion', 'SESION_REEMPLAZADA', false, 'Sesión reemplazada por límite');

        $add('roles', 'matriz_permisos', 'EDITAR_PERMISOS', true, 'Edición de la matriz de permisos');

        $add('auditoria', 'bitacora', 'CONSULTAR', false, 'Consulta de bitácora');
        $add('auditoria', 'exportacion', 'EXPORTAR', true, 'Exportación de datos');

        return $items;
    }
}
