<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use App\Modules\InscripcionesAtenciones\Support\VigenciaSettings;

final class VigenciaConfiguracionIncompletaException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  list<string>  $claves
     */
    public function __construct(public readonly array $claves = [VigenciaSettings::VENTANA_ALERTA_DIAS])
    {
        parent::__construct(
            'Falta la configuración de la ventana de alerta de vigencias. '
            .'Ejecutá el seeder de configuración del módulo.',
        );
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'VIGENCIA_CONFIGURACION_INCOMPLETA';
    }
}
