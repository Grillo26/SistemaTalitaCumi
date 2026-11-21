<?php

namespace App\Modules\Seguridad\Support;

/**
 * Etiquetas legibles de los roles fijos (solo presentación).
 */
final class RoleLabel
{
    public static function for(?string $name): string
    {
        return match ($name) {
            'superadmin' => 'Superadmin',
            'administracion' => 'Administración',
            'recepcion' => 'Recepción',
            default => $name === null || $name === '' ? 'Sin rol' : ucfirst($name),
        };
    }
}
