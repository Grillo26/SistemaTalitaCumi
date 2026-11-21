<?php

namespace App\Modules\Organizacion\Models;

use App\Modules\Organizacion\Enums\ParameterCategory;
use App\Modules\Organizacion\Enums\ParameterType;
use Database\Factories\ParametroFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Parámetro global de configuración (RF-001/RF-003).
 *
 * La tabla real se llama `configuracion` y no tiene `created_at`: la fecha de
 * alta queda en bitácora. No hay SoftDeletes ni estado activo/inactivo.
 */
class Parametro extends Model
{
    /** @use HasFactory<ParametroFactory> */
    use HasFactory;

    public const CREATED_AT = null;

    public const UPDATED_AT = 'updated_at';

    protected $table = 'configuracion';

    protected $fillable = [
        'clave',
        'categoria',
        'descripcion',
        'tipo',
        'valor',
        'valor_defecto',
        'unidad',
        'minimo',
        'maximo',
        'sensible',
        'editable',
        'visible_recepcion',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria' => ParameterCategory::class,
            'tipo' => ParameterType::class,
            'sensible' => 'boolean',
            'editable' => 'boolean',
            'visible_recepcion' => 'boolean',
            'version' => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ParametroFactory
    {
        return ParametroFactory::new();
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
                ->whereRaw('lower(clave) like ?', [$like])
                ->orWhereRaw('lower(descripcion) like ?', [$like]);
        });
    }

    public function scopeCategoria(Builder $query, ?string $categoria): Builder
    {
        if ($categoria === null || $categoria === '') {
            return $query;
        }

        return $query->where('categoria', $categoria);
    }

    public function scopeVisiblesRecepcion(Builder $query, bool $onlyVisible): Builder
    {
        return $onlyVisible ? $query->where('visible_recepcion', true) : $query;
    }
}
