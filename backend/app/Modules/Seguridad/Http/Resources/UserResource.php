<?php

namespace App\Modules\Seguridad\Http\Resources;

use App\Models\User;
use App\Modules\Seguridad\Support\RoleLabel;
use App\Modules\Seguridad\Support\UserAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación de usuario (02/RF-U07).
 *
 * Nunca incluye contraseña, hash, remember_token ni tokens.
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public static function withAbilities(User $actor, User $target, Request $request): array
    {
        return array_merge(
            (new self($target))->resolve($request),
            ['can' => UserAbilities::for($actor, $target)],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        $role = $user->roles->first();
        $sessions = $user->sesiones_activas;

        if ($sessions === null) {
            $sessions = $user->tokens()
                ->where(static fn ($query) => $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now()))
                ->count();
        }

        return [
            'id' => $user->id,
            'nombre_completo' => $user->name,
            'username' => $user->username,
            'documento' => $user->documento,
            'email' => $user->email,
            'activo' => (bool) $user->activo,
            'estado' => $user->activo ? 'activo' : 'inactivo',
            'bloqueado' => $user->estaBloqueado(),
            'rol' => $role === null ? null : [
                'id' => $role->id,
                'nombre' => $role->name,
                'label' => RoleLabel::for($role->name),
            ],
            'sucursales' => $user->branches
                ->map(static fn ($branch): array => [
                    'id' => $branch->id,
                    'codigo' => $branch->codigo,
                    'nombre' => $branch->nombre,
                ])
                ->values()
                ->all(),
            'version' => $user->version,
            'ultimo_acceso' => $user->ultimo_acceso?->toIso8601String(),
            'intentos_fallidos' => $user->intentos_fallidos,
            'bloqueado_hasta' => $user->bloqueado_hasta?->toIso8601String(),
            'motivo_estado' => $user->motivo_estado,
            'estado_cambiado_en' => $user->estado_cambiado_en?->toIso8601String(),
            'debe_cambiar_password' => (bool) $user->debe_cambiar_password,
            'sesiones_activas' => (int) $sessions,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }
}
