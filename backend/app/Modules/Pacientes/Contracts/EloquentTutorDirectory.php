<?php

namespace App\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Exceptions\TutorPrincipalRequiredException;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Models\Tutor;
use Illuminate\Support\Facades\DB;

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
        $tutor = Tutor::query()->with('parentescoCatalogo')->find($tutorId);

        if ($tutor === null) {
            throw new TutorNotFoundException($tutorId);
        }

        return $this->toResumen($tutor, false, $tutor->parentescoCatalogo);
    }

    public function tutoresDePaciente(int $pacienteId): array
    {
        return PacienteTutor::query()
            ->where('paciente_id', $pacienteId)
            ->with(['tutor.parentescoCatalogo', 'parentesco'])
            ->orderByDesc('es_principal')
            ->orderBy('tutor_id')
            ->get()
            ->filter(static fn (PacienteTutor $relacion): bool => $relacion->tutor !== null)
            ->map(fn (PacienteTutor $relacion): TutorResumen => $this->toResumen(
                $relacion->tutor,
                (bool) $relacion->es_principal,
                $relacion->parentesco ?? $relacion->tutor->parentescoCatalogo,
            ))
            ->values()
            ->all();
    }

    public function tieneTutorPrincipal(int $pacienteId): bool
    {
        return DB::table('paciente_tutor')
            ->join('tutor', 'tutor.id', '=', 'paciente_tutor.tutor_id')
            ->where('paciente_tutor.paciente_id', $pacienteId)
            ->where('paciente_tutor.es_principal', true)
            ->where('tutor.activo', true)
            ->exists();
    }

    public function tutorPrincipal(int $pacienteId): ?TutorResumen
    {
        $relacion = PacienteTutor::query()
            ->where('paciente_id', $pacienteId)
            ->where('es_principal', true)
            ->with(['tutor.parentescoCatalogo', 'parentesco'])
            ->first();

        if ($relacion?->tutor === null || ! $relacion->tutor->activo) {
            return null;
        }

        return $this->toResumen(
            $relacion->tutor,
            true,
            $relacion->parentesco ?? $relacion->tutor->parentescoCatalogo,
        );
    }

    public function asegurarTutorPrincipal(int $pacienteId): void
    {
        if (! $this->tieneTutorPrincipal($pacienteId)) {
            throw new TutorPrincipalRequiredException;
        }
    }

    private function toResumen(
        Tutor $tutor,
        bool $esPrincipal = false,
        ?Parentesco $parentesco = null,
    ): TutorResumen {
        return new TutorResumen(
            id: (int) $tutor->id,
            nombreCompleto: $tutor->nombre_completo,
            ci: $tutor->ci,
            telefono: $tutor->telefono,
            telefonoAlternativo: $tutor->telefono_alternativo,
            email: $tutor->email,
            direccion: $tutor->direccion,
            parentescoId: $parentesco?->id,
            parentesco: $parentesco?->nombre ?? $tutor->parentescoCatalogo?->nombre ?? $tutor->parentesco,
            activo: (bool) $tutor->activo,
            esPrincipal: $esPrincipal,
            sucursalRegistroId: $tutor->sucursal_registro_id === null ? null : (int) $tutor->sucursal_registro_id,
        );
    }
}
