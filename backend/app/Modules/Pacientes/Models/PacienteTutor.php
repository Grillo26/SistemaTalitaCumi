<?php

namespace App\Modules\Pacientes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asociación paciente–tutor (04.02/RF-004, 04.02/RN-003).
 */
class PacienteTutor extends Model
{
    protected $table = 'paciente_tutor';

    public $timestamps = false;

    /**
     * La tabla usa PK compuesta (paciente_id, tutor_id), no una columna `id`.
     * Sin esto, Eloquent intenta `returning "id"` en PostgreSQL.
     */
    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'paciente_id',
        'tutor_id',
        'parentesco_id',
        'es_principal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    public function parentesco(): BelongsTo
    {
        return $this->belongsTo(Parentesco::class);
    }
}
