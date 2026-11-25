<?php

namespace App\Modules\Pacientes\Models;

use App\Models\User;
use Database\Factories\FichaIngresoRevisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Revisión inmutable de la ficha de ingreso (04.03/RF-005).
 *
 * El snapshot completo contiene datos sensibles y solo se expone con
 * `ficha_ingreso.ver_sensibles`. La bitácora guarda metadatos y banderas.
 */
class FichaIngresoRevision extends Model
{
    /** @use HasFactory<FichaIngresoRevisionFactory> */
    use HasFactory;

    protected $table = 'ficha_ingreso_revision';

    public $timestamps = false;

    protected $fillable = [
        'ficha_ingreso_id',
        'version',
        'accion',
        'snapshot',
        'campos_modificados',
        'usuario_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
            'campos_modificados' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function newFactory(): FichaIngresoRevisionFactory
    {
        return FichaIngresoRevisionFactory::new();
    }

    public function ficha(): BelongsTo
    {
        return $this->belongsTo(FichaIngreso::class, 'ficha_ingreso_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
