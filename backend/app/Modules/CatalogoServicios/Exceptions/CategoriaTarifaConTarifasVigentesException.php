<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoriaTarifaConTarifasVigentesException extends CatalogoServiciosDomainException
{
    public function __construct(
        public readonly string $categoriaNombre,
        public readonly int $activeTariffsCount,
    ) {
        parent::__construct(
            'No se puede desactivar una categoría con tarifas vigentes. Cerrá primero esas vigencias.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CATEGORIA_CON_TARIFAS_VIGENTES';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'active_tariffs_count' => $this->activeTariffsCount,
            'categoria' => ['nombre' => $this->categoriaNombre],
        ], $this->status());
    }
}
