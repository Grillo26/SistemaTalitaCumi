<?php

namespace App\Modules\Profesionales\Models;

use App\Modules\Organizacion\Models\Branch;
use Database\Factories\ProfesionalSucursalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relación profesional–sucursal (03/RF-E06 / 03/RN-07, 03/RN-10).
 *
 * Se activa y desactiva con motivo, sin borrado físico. El índice parcial
 * evita duplicar una relación activa.
 */
class ProfesionalSucursal extends Model
{
    /** @use HasFactory<ProfesionalSucursalFactory> */
    use HasFactory;

    protected $table = 'profesional_sucursal';

    protected $fillable = [
        'profesional_id',
        'sucursal_id',
        'activo',
        'motivo',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function newFactory(): ProfesionalSucursalFactory
    {
        return ProfesionalSucursalFactory::new();
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_id');
    }
}
