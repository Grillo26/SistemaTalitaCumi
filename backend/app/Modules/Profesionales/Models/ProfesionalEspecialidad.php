<?php

namespace App\Modules\Profesionales\Models;

use App\Modules\Profesionales\Enums\TipoEspecialidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relación profesional–especialidad (03/RF-E04, 03/RF-E07 / 03/RN-02).
 *
 * `activo` conserva el historial sin borrado físico. Los índices únicos
 * parciales de la migración garantizan una relación activa por par y una
 * sola principal activa por profesional.
 */
class ProfesionalEspecialidad extends Model
{
    protected $table = 'profesional_especialidad';

    protected $fillable = [
        'profesional_id',
        'especialidad_id',
        'tipo',
        'activo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoEspecialidad::class,
            'activo' => 'boolean',
        ];
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }
}
