<?php

namespace App\Modules\Pacientes\Models;

use App\Models\User;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de estados del paciente (04.01/RN-004/RN-005).
 *
 * Solo inserción. Sin `updated_at`.
 */
class EstadoPacienteHistorial extends Model
{
    protected $table = 'estado_paciente_historial';

    public $timestamps = false;

    protected $fillable = [
        'paciente_id',
        'estado_anterior',
        'estado_nuevo',
        'motivo',
        'usuario_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado_anterior' => EstadoPaciente::class,
            'estado_nuevo' => EstadoPaciente::class,
            'created_at' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
