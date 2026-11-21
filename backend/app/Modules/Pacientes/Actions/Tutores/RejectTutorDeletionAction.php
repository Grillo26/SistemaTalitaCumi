<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Modules\Pacientes\Exceptions\TutorDeleteNotAllowedException;

/**
 * Rechaza siempre la eliminación física de tutores (04.02/RF-011).
 */
final class RejectTutorDeletionAction
{
    public function __invoke(): never
    {
        throw new TutorDeleteNotAllowedException;
    }
}
