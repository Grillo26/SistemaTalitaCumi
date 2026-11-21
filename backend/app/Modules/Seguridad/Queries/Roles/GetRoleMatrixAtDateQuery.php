<?php

namespace App\Modules\Seguridad\Queries\Roles;

use App\Modules\Seguridad\Exceptions\RoleNotFoundException;
use App\Modules\Seguridad\Models\BitacoraEntry;
use Carbon\CarbonImmutable;
use Spatie\Permission\Models\Role;

/**
 * Matriz vigente de un rol en una fecha (02/RF-R14).
 *
 * Reconstruye desde la bitácora: toma el último evento `matriz_permisos`
 * anterior al fin del día consultado. No hay tabla de versiones paralela.
 */
final class GetRoleMatrixAtDateQuery
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $roleId, string $date): array
    {
        $role = Role::query()
            ->whereKey($roleId)
            ->where('guard_name', 'web')
            ->first();

        if ($role === null) {
            throw new RoleNotFoundException($roleId);
        }

        $endOfDay = CarbonImmutable::createFromFormat(
            'Y-m-d',
            $date,
            (string) config('app.timezone'),
        )->endOfDay()->utc();

        $entry = BitacoraEntry::query()
            ->where('entidad', 'matriz_permisos')
            ->where('entidad_id', $role->id)
            ->where('created_at', '<=', $endOfDay)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($entry === null) {
            return [
                'role_id' => $role->id,
                'fecha' => $date,
                'encontrado' => false,
                'permisos' => [],
                'version' => null,
                'version_actual' => (int) $role->version,
                'registrado_en' => null,
            ];
        }

        $payload = $entry->datos_nuevos ?? [];

        return [
            'role_id' => $role->id,
            'fecha' => $date,
            'encontrado' => true,
            'permisos' => $payload['permisos'] ?? [],
            'version' => $payload['version'] ?? null,
            'version_actual' => (int) $role->version,
            'registrado_en' => $entry->created_at?->toIso8601String(),
        ];
    }
}
