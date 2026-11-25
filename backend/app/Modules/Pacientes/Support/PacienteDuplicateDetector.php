<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Rules\PacienteDuplicateRule;

/**
 * Detección de duplicados global (04.01/RF-003, 04.01/RF-016).
 *
 * Detecta sobre toda la base para poder avisar de coincidencias fuera del
 * alcance. El presentador se encarga de no filtrar datos del menor.
 */
final class PacienteDuplicateDetector
{
    public function __construct(private readonly PacienteDuplicateRule $rule) {}

    /**
     * @param  array{nombre: string, apellido_paterno: string, apellido_materno: ?string, fecha_nacimiento: string, documento: ?string, tutor_principal_id: ?int}  $input
     */
    public function detect(array $input, ?int $excludePacienteId = null): PacienteDuplicateResult
    {
        $normalized = [
            'nombre' => PacienteNormalizer::key($input['nombre']),
            'apellido_paterno' => PacienteNormalizer::key($input['apellido_paterno']),
            'apellido_materno' => $input['apellido_materno'] === null
                ? null
                : PacienteNormalizer::key($input['apellido_materno']),
            'fecha_nacimiento' => $input['fecha_nacimiento'],
            'tutor_principal_id' => $input['tutor_principal_id'],
        ];

        $documentKey = PacienteNormalizer::documentKey($input['documento']);

        $hard = null;

        if ($documentKey !== null) {
            $hard = Paciente::query()
                ->when($excludePacienteId !== null, fn ($query) => $query->whereKeyNot($excludePacienteId))
                ->where('documento_normalizado', $documentKey)
                ->first();
        }

        $candidates = Paciente::query()
            ->when($excludePacienteId !== null, fn ($query) => $query->whereKeyNot($excludePacienteId))
            ->whereDate('fecha_nacimiento', $input['fecha_nacimiento'])
            ->where(function ($query) use ($normalized): void {
                $query->where(function ($query) use ($normalized): void {
                    $query
                        ->where('nombre_normalizado', $normalized['nombre'])
                        ->where('apellido_paterno_normalizado', $normalized['apellido_paterno']);
                });

                if ($normalized['tutor_principal_id'] !== null) {
                    $query->orWhereHas(
                        'relacionesTutor',
                        static fn ($relaciones) => $relaciones
                            ->where('tutor_id', $normalized['tutor_principal_id'])
                            ->where('es_principal', true),
                    );
                }
            })
            ->with(['relacionesTutor' => static fn ($query) => $query->where('es_principal', true)])
            ->limit(20)
            ->get();

        $softMatches = [];

        foreach ($candidates as $candidate) {
            $tipo = $this->rule->classify($normalized, [
                'id' => (int) $candidate->id,
                'nombre' => (string) $candidate->nombre_normalizado,
                'apellido_paterno' => (string) $candidate->apellido_paterno_normalizado,
                'apellido_materno' => $candidate->apellido_materno_normalizado,
                'fecha_nacimiento' => $candidate->fecha_nacimiento->toDateString(),
                'tutor_principal_id' => $candidate->relacionesTutor->first()?->tutor_id,
            ]);

            if ($tipo !== null) {
                $softMatches[] = ['paciente' => $candidate, 'tipo' => $tipo];
            }
        }

        return new PacienteDuplicateResult($hard, $softMatches);
    }
}
