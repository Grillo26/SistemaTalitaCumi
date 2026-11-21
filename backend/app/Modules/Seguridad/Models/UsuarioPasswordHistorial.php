<?php

namespace App\Modules\Seguridad\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hash de una contraseña anterior (RF-A02 / RN-12).
 */
class UsuarioPasswordHistorial extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'usuario_password_historial';

    protected $fillable = [
        'usuario_id',
        'password_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
