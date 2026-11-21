<?php

namespace App\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Exceptions\TutorPrincipalRequiredException;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Tutor;

/**
 * Implementación Eloquent del directorio de tutores.
 */
final class EloquentTutorDirectory implements TutorDirectoryContract
{
    public function existe(int $tutorId): bool
    {
        return Tutor::query()->whereKey($tutorId)->exists();
    }

    public function estaActivo(int $tutorId): bool
    {
        return Tutor::query()->whereKey($tutorId)->where('activo', true)->exists();
    }

    public function resumen(int $tutorId): TutorResumen
    {
        $tutor = Tutor::query()->find($tutorId);

        if ($tutor === null) {
            throw new TutorNotFoundException($tutorId);
        }

        return $this->toResumen($tutor);
    }

    public function tutoresDePaciente(int $pacienteId): array
    {
        return PacienteTutor::query()
            ->where('paciente_id', $pacienteId)
            ->with('tutor')
            ->orderByDesc('es_principal')
            ->orderBy('tutor_id')
            ->get()
            ->filter(static fn (PacienteTutor $relacion): bool => $relacion->tutor !== null)
            ->map(fn (PacienteTutor $relacion): TutorResumen => $this->toResumen(
                $relacion->tutor,
                (bool) $relacion->es_principal,
            ))
            ->values()
            ->all();
    }

    public function tieneTutorPrincipal(int $pacienteId): bool
    {
        return PacienteTutor::query()
            ->where('paciente_id', $pacienteId)
            ->where('es_principal', true)
            ->exists();
    }

    public function tutorPrincipal(int $pacienteId): ?TutorResumen
    {
        $relacion = PacienteTutor::query()
            ->where('paciente_id', $pacienteId)
            ->where('es_principal', true)
            ->with('tutor')
            ->first();

        return $relacion?->tutor === null ? null : $this->toResumen($relacion->tutor, true);
    }

    public function asegurarTutorPrincipal(int $pacienteId): void
    {
        if (! $this->tieneTutorPrincipal($pacienteId)) {
            throw new TutorPrincipalRequiredException;
        }
    }

    private function toResumen(Tutor $tutor, bool $esPrincipal = false): TutorResumen
    {
        return new TutorResumen(
            id: (int) $tutor->id,
            nombreCompleto: $tutor->nombre_completo,
            ci: $tutor->ci,
            telefono: $tutor->telefono,
            telefonoAlternativo: $tutor->telefono_alternativo,
            email: $tutor->email,
            direccion: $tutor->direccion,
            parentesco: $tutor->parentesco,
            activo: (bool) $tutor->activo,
            esPrincipal: $esPrincipal,
        );
    }
}
