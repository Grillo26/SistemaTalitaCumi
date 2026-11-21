<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Cambio de la matriz de permisos de un rol (02/RF-R04, 02/RF-R10).
 */
final class RolePermissionsUpdated
{
    use Dispatchable;

    /**
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    public function __construct(
        public readonly int $roleId,
        public readonly array $before,
        public readonly array $after,
        public readonly ?int $beforeVersion,
        public readonly int $version,
        public readonly ?int $actorId,
        public readonly ?string $ip,
        public readonly ?string $motivo,
        public readonly bool $baseline = false,
    ) {}
}
