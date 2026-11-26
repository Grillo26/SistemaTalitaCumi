<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Modules\InscripcionesAtenciones\Support\PaqueteSettings;

/**
 * Defaults de configuración para el formulario de paquetes.
 */
final class GetPaqueteOpcionesQuery
{
    public function __construct(private readonly PaqueteSettings $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'defaults' => $this->settings->defaults(),
        ];
    }
}
