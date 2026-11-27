<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Exceptions\VigenciaConfiguracionIncompletaException;
use App\Modules\InscripcionesAtenciones\Rules\VentanaAlertaRule;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;

/**
 * [Vigencia y Renovación] RF-003 / RN-007 / RNF-011.
 *
 * La ventana vive en configuración; esta clase no la hardcodea.
 */
final class VigenciaSettings
{
    public const VENTANA_ALERTA_DIAS = 'vigencia.ventana_alerta_dias';

    public function __construct(private readonly ParameterReaderContract $parameters) {}

    public function ventanaAlertaDias(): int
    {
        try {
            $value = (int) $this->parameters->obtener(self::VENTANA_ALERTA_DIAS)->valor;
        } catch (ParameterNotFoundException) {
            throw new VigenciaConfiguracionIncompletaException;
        }

        if ($value < VentanaAlertaRule::MINIMO || $value > VentanaAlertaRule::MAXIMO) {
            throw new VigenciaConfiguracionIncompletaException([self::VENTANA_ALERTA_DIAS]);
        }

        return $value;
    }
}
