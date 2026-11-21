<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Organizacion\Models\Branch;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuario del sistema — módulo 02 Usuarios, roles y seguridad.
 *
 * Solo Superadmin, Administración y Recepción tienen cuenta. El nombre
 * completo vive en `name`; el rol se administra con Spatie. No hay borrado
 * físico: el usuario se activa o desactiva.
 */
#[Fillable([
    'name',
    'username',
    'documento',
    'email',
    'password',
    'activo',
    'motivo_estado',
    'estado_cambiado_en',
    'ultimo_acceso',
    'intentos_fallidos',
    'bloqueado_hasta',
    'debe_cambiar_password',
    'password_actualizada_en',
    'version',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'estado_cambiado_en' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'intentos_fallidos' => 'integer',
            'bloqueado_hasta' => 'datetime',
            'debe_cambiar_password' => 'boolean',
            'password_actualizada_en' => 'datetime',
            'version' => 'integer',
        ];
    }

    /**
     * Sucursales asignadas (RF-U05, RF-U16 / RN-03).
     *
     * @return BelongsToMany<Branch, $this>
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'usuario_sucursal',
            'usuario_id',
            'sucursal_id',
        );
    }

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.mb_strtolower($term).'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query
                ->whereRaw('lower(name) like ?', [$like])
                ->orWhereRaw('lower(email) like ?', [$like])
                ->orWhereRaw('lower(username) like ?', [$like])
                ->orWhereRaw('lower(documento) like ?', [$like]);
        });
    }

    public function scopeEstado(Builder $query, ?string $estado): Builder
    {
        return match ($estado) {
            'activo' => $query->where('activo', true),
            'inactivo' => $query->where('activo', false),
            'bloqueado' => $query
                ->whereNotNull('bloqueado_hasta')
                ->where('bloqueado_hasta', '>', now()),
            default => $query,
        };
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture();
    }
}
