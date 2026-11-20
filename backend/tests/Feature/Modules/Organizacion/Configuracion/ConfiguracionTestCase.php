<?php

namespace Tests\Feature\Modules\Organizacion\Configuracion;

use App\Modules\Organizacion\Models\Parametro;
use Tests\Feature\Modules\Organizacion\OrganizacionTestCase;

abstract class ConfiguracionTestCase extends OrganizacionTestCase
{
    protected function parametro(string $clave): Parametro
    {
        return Parametro::query()->where('clave', $clave)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(string $clave, mixed $valor, array $overrides = []): array
    {
        return array_merge([
            'valor' => $valor,
            'version' => $this->parametro($clave)->version,
        ], $overrides);
    }
}
