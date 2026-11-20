<?php

namespace App\Modules\Organizacion\Support;

use App\Modules\Organizacion\Enums\ParameterCategory;
use App\Modules\Organizacion\Enums\ParameterType;

/**
 * DTO inmutable del contrato de lectura de parámetros (RF-012).
 */
final class ParameterValue
{
    public function __construct(
        public readonly string $clave,
        public readonly mixed $valor,
        public readonly ParameterType $tipo,
        public readonly ?string $unidad,
        public readonly ParameterCategory $categoria,
        public readonly bool $sensible,
        public readonly bool $visibleRecepcion,
        public readonly ?string $actualizadoEn,
    ) {}
}
