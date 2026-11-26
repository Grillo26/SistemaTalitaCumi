<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Modules\InscripcionesAtenciones\Exceptions\VigenciaConfiguracionIncompletaException;
use App\Modules\InscripcionesAtenciones\Support\VigenciaSettings;
use App\Modules\Organizacion\Contracts\ParameterAdminContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;

/**
 * [Vigencia y Renovación] RF-003 / RNF-011.
 */
final class GetVentanaAlertaQuery
{
    public function __construct(private readonly ParameterAdminContract $parameters) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        try {
            return $this->parameters->detalle(VigenciaSettings::VENTANA_ALERTA_DIAS)->toArray();
        } catch (ParameterNotFoundException) {
            throw new VigenciaConfiguracionIncompletaException;
        }
    }
}
