<?php

namespace App\Modules\CatalogoServicios\Actions\TiposDiagnostico;

use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoDeletionNotAllowedException;

/**
 * Los tipos de diagnóstico no se eliminan (RF-D09 / RN-D07).
 */
final class RejectTipoDiagnosticoDeletionAction
{
    public function __invoke(int $tipoDiagnosticoId): never
    {
        throw new TipoDiagnosticoDeletionNotAllowedException;
    }
}
