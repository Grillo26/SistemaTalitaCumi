<?php

namespace App\Modules\Profesionales\Listeners;

use App\Modules\Profesionales\Events\Directorio\ProfesionalCreated;
use App\Modules\Profesionales\Events\Directorio\ProfesionalEspecialidadesUpdated;
use App\Modules\Profesionales\Events\Directorio\ProfesionalStatusChanged;
use App\Modules\Profesionales\Events\Directorio\ProfesionalSucursalChanged;
use App\Modules\Profesionales\Events\Directorio\ProfesionalUpdated;
use App\Modules\Profesionales\Events\Especialidades\EspecialidadCreated;
use App\Modules\Profesionales\Events\Especialidades\EspecialidadStatusChanged;
use App\Modules\Profesionales\Events\Especialidades\EspecialidadUpdated;
use App\Modules\Profesionales\Support\ProfesionalValidatorCacheKeys;
use Illuminate\Support\Facades\Cache;

/**
 * Invalida el validador con una época explícita (03/RT-07).
 *
 * El store file no soporta tags; incrementar la época invalida todas las
 * combinaciones sin recorrer claves. TTL corto como segunda barrera.
 */
final class InvalidateProfesionalValidatorCache
{
    public function handle(
        ProfesionalCreated|ProfesionalUpdated|ProfesionalEspecialidadesUpdated|ProfesionalStatusChanged|
        ProfesionalSucursalChanged|EspecialidadCreated|EspecialidadUpdated|EspecialidadStatusChanged $event,
    ): void {
        $epoch = (int) Cache::get(ProfesionalValidatorCacheKeys::EPOCH_KEY, 0);

        Cache::forever(ProfesionalValidatorCacheKeys::EPOCH_KEY, $epoch + 1);
    }
}
