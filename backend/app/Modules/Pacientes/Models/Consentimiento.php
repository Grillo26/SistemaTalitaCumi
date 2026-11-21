<?php

namespace App\Modules\Pacientes\Models;

use App\Models\User;
use App\Modules\Pacientes\Enums\ResultadoConsentimiento;
use Database\Factories\ConsentimientoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Evento de consentimiento (04.03 Fase B).
 *
 * Cada fila es inmutable. La revocación es una fila nueva con
 * `resultado=revocado` y `revoca_a_id` apuntando al consentimiento original.
 * El motivo no se copia a la bitácora.
 */
class Consentimiento extends Model
{
    /** @use HasFactory<ConsentimientoFactory> */
    use HasFactory;

    protected $table = 'consentimiento';

    public $timestamps = false;

    protected $fillable = [
        'ficha_ingreso_id',
        'consentimiento_tipo_id',
        'tutor_id',
        'resultado',
        'fecha',
        'motivo',
        'usuario_id',
        'revoca_a_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resultado' => ResultadoConsentimiento::class,
            'fecha' => 'date',
            'revoca_a_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Los consentimientos son inmutables: no se pueden editar.');
        });

        static::deleting(function (): never {
            throw new LogicException('Los consentimientos no se eliminan; se revocan con un evento nuevo.');
        });
    }

    protected static function newFactory(): ConsentimientoFactory
    {
        return ConsentimientoFactory::new();
    }

    public function ficha(): BelongsTo
    {
        return $this->belongsTo(FichaIngreso::class, 'ficha_ingreso_id');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoTipo::class, 'consentimiento_tipo_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function revocaA(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revoca_a_id');
    }

    public function revocaciones(): HasMany
    {
        return $this->hasMany(self::class, 'revoca_a_id');
    }

    /**
     * Consentimientos otorgados que no tienen una revocación posterior.
     */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query
            ->where('resultado', ResultadoConsentimiento::OTORGADO->value)
            ->whereNotExists(function ($subquery): void {
                $subquery
                    ->select(DB::raw(1))
                    ->from('consentimiento as revocacion')
                    ->whereColumn('revocacion.revoca_a_id', 'consentimiento.id')
                    ->where('revocacion.resultado', ResultadoConsentimiento::REVOCADO->value);
            });
    }

    public function estaVigente(): bool
    {
        if ($this->resultado !== ResultadoConsentimiento::OTORGADO) {
            return false;
        }

        return ! $this->revocaciones()
            ->where('resultado', ResultadoConsentimiento::REVOCADO->value)
            ->exists();
    }

    public function fueRevocado(): bool
    {
        return $this->resultado === ResultadoConsentimiento::REVOCADO;
    }
}
