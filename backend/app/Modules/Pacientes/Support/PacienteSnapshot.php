<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\Paciente;

/**
 * Snapshot seguro del paciente para bitácora.
 *
 * Nunca guarda contenido clínico. Alergias, condiciones y observaciones se
 * representan solo por presencia (booleanos).
 */
final class PacienteSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Paciente $paciente): array
    {
        return [
            'id' => (int) $paciente->id,
            'codigo' => $paciente->codigo,
            'nombre' => $paciente->nombre,
            'apellido_paterno' => $paciente->apellido_paterno,
            'apellido_materno' => $paciente->apellido_materno,
            'fecha_nacimiento' => $paciente->fecha_nacimiento->toDateString(),
            'sexo' => $paciente->sexo->value,
            'documento' => $paciente->ci,
            'tipo_diagnostico_id' => $paciente->tipo_diagnostico_id,
            'sucursal_registro_id' => (int) $paciente->sucursal_registro_id,
            'estado' => $paciente->estado->value,
            'tiene_alergias' => filled($paciente->alergias),
            'tiene_condiciones_medicas' => filled($paciente->condiciones_medicas),
            'tiene_observaciones' => filled($paciente->observaciones),
            'version' => (int) $paciente->version,
            'updated_at' => $paciente->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    public static function sensitiveFieldsPresent(array $attributes): array
    {
        return array_values(array_intersect(
            ['alergias', 'condiciones_medicas', 'observaciones'],
            array_keys($attributes),
        ));
    }
}
