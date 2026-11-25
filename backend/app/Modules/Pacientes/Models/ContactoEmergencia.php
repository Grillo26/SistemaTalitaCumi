<?php

namespace App\Modules\Pacientes\Models;

use App\Modules\Pacientes\Rules\TutorPhoneRule;
use App\Modules\Pacientes\Support\PacienteNormalizer;
use Database\Factories\ContactoEmergenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contacto de emergencia adicional de un paciente (04.02/RF-012).
 *
 * No es un tutor global: vive en la ficha del paciente. El teléfono se
 * normaliza y se enmascara en las respuestas según el permiso del actor.
 */
class ContactoEmergencia extends Model
{
    /** @use HasFactory<ContactoEmergenciaFactory> */
    use HasFactory;

    protected $table = 'contacto_emergencia';

    protected $fillable = [
        'paciente_id',
        'nombre_completo',
        'parentesco_id',
        'telefono',
        'telefono_normalizado',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parentesco_id' => 'integer',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ContactoEmergencia $contacto): void {
            $contacto->nombre_normalizado = PacienteNormalizer::key($contacto->nombre_completo);
            $contacto->telefono_normalizado = TutorPhoneRule::normalize($contacto->telefono) ?? '';
        });
    }

    protected static function newFactory(): ContactoEmergenciaFactory
    {
        return ContactoEmergenciaFactory::new();
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function parentesco(): BelongsTo
    {
        return $this->belongsTo(Parentesco::class);
    }
}
