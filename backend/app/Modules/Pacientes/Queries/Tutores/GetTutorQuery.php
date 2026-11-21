<?php

namespace App\Modules\Pacientes\Queries\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Support\TutorAccessScope;

/**
 * Ficha de tutor (04.02/RF-008).
 */
final class GetTutorQuery
{
    public function __construct(private readonly TutorAccessScope $scope) {}

    public function __invoke(int $tutorId, User $actor): Tutor
    {
        $tutor = Tutor::query()->find($tutorId);

        if ($tutor === null) {
            throw new TutorNotFoundException($tutorId);
        }

        $this->scope->assertCanAccess($actor, $tutor);

        return $tutor;
    }
}
