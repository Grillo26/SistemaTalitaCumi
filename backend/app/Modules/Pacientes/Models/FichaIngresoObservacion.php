<?php

namespace App\Modules\Pacientes\Models;

use App\Models\User;
use Database\Factories\FichaIngresoObservacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Observación fechada de la ficha de ingreso (04.03 Fase C, RF-006).
 *
 * Es inmutable: las correcciones se registran como una observación nueva que
 * referencia a la anterior. El texto es sensible y no se copia a bitácora.
 */
class FichaIngresoObservacion extends Model
{
    /** @use HasFactory<FichaIngresoObservacionFactory> */
    use HasFactory;

    protected $table = 'ficha_ingreso_observacion';

    public $timestamps = false;

    protected $fillable = [
        'ficha_ingreso_id',
        'fecha',
        'texto',
        'observacion_referencia_id',
        'usuario_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'observacion_referencia_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Las observaciones son inmutables: no se pueden editar.');
        });

        static::deleting(function (): never {
            throw new LogicException('Las observaciones no se eliminan; agregá una corrección que referencie la anterior.');
        });
    }

    protected static function newFactory(): FichaIngresoObservacionFactory
    {
        return FichaIngresoObservacionFactory::new();
    }

    public function ficha(): BelongsTo
    {
        return $this->belongsTo(FichaIngreso::class, 'ficha_ingreso_id');
    }

    public function referencia(): BelongsTo
    {
        return $this->belongsTo(self::class, 'observacion_referencia_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
