<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Actions\UpdateParameterAction;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Support\ParameterValueCaster;

/**
 * Implementación real del contrato administrativo de parámetros.
 */
final class EloquentParameterAdmin implements ParameterAdminContract
{
    public function __construct(private readonly UpdateParameterAction $update) {}

    public function detalle(string $clave): ParameterAdminValue
    {
        return $this->toValue($this->find($clave));
    }

    public function actualizar(
        string $clave,
        string $valor,
        int $version,
        ?int $actorId,
        ?string $motivo = null,
    ): ParameterAdminValue {
        $parametro = $this->find($clave);

        $actualizado = ($this->update)(
            $parametro->id,
            ['valor' => $valor, 'motivo' => $motivo],
            $version,
            $actorId,
        );

        return $this->toValue($actualizado);
    }

    private function find(string $clave): Parametro
    {
        $parametro = Parametro::query()->where('clave', $clave)->first();

        if ($parametro === null) {
            throw new ParameterNotFoundException($clave);
        }

        return $parametro;
    }

    private function toValue(Parametro $parametro): ParameterAdminValue
    {
        return new ParameterAdminValue(
            id: (int) $parametro->id,
            clave: (string) $parametro->clave,
            valor: ParameterValueCaster::cast($parametro->tipo, $parametro->valor),
            valorDefecto: ParameterValueCaster::cast($parametro->tipo, $parametro->valor_defecto),
            tipo: $parametro->tipo->value,
            unidad: $parametro->unidad,
            minimo: $parametro->minimo,
            maximo: $parametro->maximo,
            version: (int) $parametro->version,
            editable: (bool) $parametro->editable,
            actualizadoEn: $parametro->updated_at?->toIso8601String(),
        );
    }
}
