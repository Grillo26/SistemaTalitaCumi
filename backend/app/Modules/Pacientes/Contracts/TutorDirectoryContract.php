<?php

namespace App\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Exceptions\TutorPrincipalRequiredException;

/**
 * Directorio de tutores para 04.01 y consumidores internos.
 */
interface TutorDirectoryContract
{
    public function existe(int $tutorId): bool;

    public function estaActivo(int $tutorId): bool;

    /**
     * @throws TutorNotFoundException
     */
    public function resumen(int $tutorId): TutorResumen;

    /**
     * @return list<TutorResumen>
     */
    public function tutoresDePaciente(int $pacienteId): array;

    /**
     * Exige que exista un tutor principal ACTIVO.
     */
    public function tieneTutorPrincipal(int $pacienteId): bool;

    /**
     * Tutor principal activo; null si no existe o está inactivo (04.02/RN-007).
     */
    public function tutorPrincipal(int $pacienteId): ?TutorResumen;

    /**
     * @throws TutorPrincipalRequiredException
     */
    public function asegurarTutorPrincipal(int $pacienteId): void;
}
