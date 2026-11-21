<?php

namespace App\Modules\Pacientes\Models;

use App\Modules\Pacientes\Support\PacienteNormalizer;
use Database\Factories\ParentescoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de parentescos (04.02/RNF-012).
 *
 * Los valores iniciales son una propuesta a validar con el cliente.
 */
class Parentesco extends Model
{
    /** @use HasFactory<ParentescoFactory> */
    use HasFactory;

    protected $table = 'parentesco';

    protected $fillable = [
        'nombre',
        'activo',
        'orden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Parentesco $parentesco): void {
            $parentesco->nombre_normalizado = PacienteNormalizer::key($parentesco->nombre);
        });
    }

    protected static function newFactory(): ParentescoFactory
    {
        return ParentescoFactory::new();
    }

    public function scopeActivo(Builder $query, bool $activo = true): Builder
    {
        return $query->where('activo', $activo);
    }

    public function scopeOrdenado(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
