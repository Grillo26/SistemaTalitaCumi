<?php

namespace Tests\Support;

use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Enums\ParameterCategory;
use App\Modules\Organizacion\Enums\ParameterType;
use App\Modules\Organizacion\Support\ParameterValue;

/**
 * Lector de parámetros en memoria para tests unitarios.
 */
final class FakeParameterReaderContract implements ParameterReaderContract
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(private array $values = []) {}

    public function obtener(string $clave): ParameterValue
    {
        return new ParameterValue(
            clave: $clave,
            valor: $this->values[$clave] ?? null,
            tipo: ParameterType::TEXTO,
            unidad: null,
            categoria: ParameterCategory::ZONA_HORARIA,
            sensible: false,
            visibleRecepcion: false,
            actualizadoEn: null,
        );
    }

    public function obtenerVarios(array $claves): array
    {
        $values = [];

        foreach ($claves as $clave) {
            $values[$clave] = $this->obtener($clave);
        }

        return $values;
    }

    public function porCategoria(string $categoria): array
    {
        return [];
    }
}
