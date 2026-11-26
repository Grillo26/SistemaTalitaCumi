<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class CategoriaTarifaNotFoundException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $categoriaTarifaId)
    {
        parent::__construct('La categoría de tarifa indicada no existe.');
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'CATEGORIA_TARIFA_NOT_FOUND';
    }
}
