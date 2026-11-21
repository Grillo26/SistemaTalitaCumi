<?php

namespace App\Modules\Profesionales\Actions\Directorio;

use App\Models\User;
use App\Modules\Profesionales\Actions\Directorio\Concerns\ChangesProfesionalStatus;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Support\ProfesionalAccessScope;

/**
 * Activar profesional (03/RF-D07 / 03/RN-20).
 */
final class ActivateProfesionalAction
{
    use ChangesProfesionalStatus;

    public function __construct(private readonly ProfesionalAccessScope $scope) {}

    public function __invoke(
        Profesional $profesional,
        string $reason,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): Profesional {
        return $this->changeStatus($profesional, true, $reason, $expectedVersion, $actor, $ip);
    }
}
