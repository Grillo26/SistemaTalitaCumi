<?php

namespace App\Modules\InscripcionesAtenciones\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot de una especialidad requerida por el diagnóstico (RN-D11).
 */
class AtencionDiagnosticoRequisito extends Model
{
    protected $table = 'atencion_diagnostico_requisito';

    public $timestamps = false;

    protected $fillable = [
        'atencion_diagnostico_id',
        'especialidad_id',
        'especialidad_nombre',
        'cantidad_minima',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'atencion_diagnostico_id' => 'integer',
            'especialidad_id' => 'integer',
            'cantidad_minima' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function atencionDiagnostico(): BelongsTo
    {
        return $this->belongsTo(AtencionDiagnostico::class, 'atencion_diagnostico_id');
    }
}
