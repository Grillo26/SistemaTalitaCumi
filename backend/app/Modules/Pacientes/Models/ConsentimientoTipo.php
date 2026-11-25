<?php

namespace App\Modules\Pacientes\Models;

use Database\Factories\ConsentimientoTipoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de tipos de consentimiento (04.03 Fase B).
 *
 * Administrable por Superadmin. No se elimina: se activa o desactiva.
 * Los tipos iniciales son una propuesta a validar con el cliente.
 */
class ConsentimientoTipo extends Model
{
    /** @use HasFactory<ConsentimientoTipoFactory> */
    use HasFactory;

    protected $table = 'consentimiento_tipo';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'orden',
        'activo',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activo' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function newFactory(): ConsentimientoTipoFactory
    {
        return ConsentimientoTipoFactory::new();
    }

    public function consentimientos(): HasMany
    {
        return $this->hasMany(Consentimiento::class, 'consentimiento_tipo_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
