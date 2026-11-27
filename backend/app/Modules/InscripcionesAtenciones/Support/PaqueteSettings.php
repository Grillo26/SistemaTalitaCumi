<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteConfiguracionIncompletaException;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;

/**
 * Defaults de paquetes leídos de configuración; no se hardcodean en Rules.
 */
final class PaqueteSettings
{
    public const CANTIDAD_SESIONES_DEFAULT = 'paquete.cantidad_sesiones_default';

    public const DURACION_SESION_MINUTOS = 'duracion_sesion_minutos';

    public function __construct(private readonly ParameterReaderContract $parameters) {}

    public function cantidadSesionesDefault(): int
    {
        return (int) $this->value(self::CANTIDAD_SESIONES_DEFAULT);
    }

    public function duracionMinutosDefault(): int
    {
        return (int) $this->value(self::DURACION_SESION_MINUTOS);
    }

    /**
     * @return array{cantidad_sesiones_default: int, duracion_minutos_default: int}
     */
    public function defaults(): array
    {
        return [
            'cantidad_sesiones_default' => $this->cantidadSesionesDefault(),
            'duracion_minutos_default' => $this->duracionMinutosDefault(),
        ];
    }

    private function value(string $key): mixed
    {
        try {
            return $this->parameters->obtener($key)->valor;
        } catch (ParameterNotFoundException) {
            throw new PaqueteConfiguracionIncompletaException([$key]);
        }
    }
}
