<?php

namespace App\Modules\Organizacion\Models;

use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Área global (módulo 05).
 *
 * En este submódulo es un catálogo de solo lectura para `sala.area_id`.
 * No se administran colores ni CRUD de áreas aquí.
 */
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    protected $table = 'area';

    protected $fillable = [
        'nombre',
        'color',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    protected static function newFactory(): AreaFactory
    {
        return AreaFactory::new();
    }

    public function salas(): HasMany
    {
        return $this->hasMany(Sala::class);
    }
}
