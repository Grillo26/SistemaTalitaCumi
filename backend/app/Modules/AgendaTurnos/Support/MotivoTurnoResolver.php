<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\OrigenMotivoTurno;
use App\Modules\AgendaTurnos\Models\MotivoTurno;

/**
 * Resolución del catálogo de motivos a datos puros.
 */
final class MotivoTurnoResolver
{
    public function obtener(int $id): ?MotivoTurnoData
    {
        $motivo = MotivoTurno::query()->find($id);

        return $motivo === null ? null : $this->data($motivo);
    }

    public function data(MotivoTurno $motivo): MotivoTurnoData
    {
        return new MotivoTurnoData(
            id: (int) $motivo->id,
            codigo: (string) $motivo->codigo,
            nombre: (string) $motivo->nombre,
            aplicaReprogramar: (bool) $motivo->aplica_reprogramar,
            aplicaCancelar: (bool) $motivo->aplica_cancelar,
            origen: $motivo->origen instanceof OrigenMotivoTurno ? $motivo->origen : null,
            descuentaSaldo: $motivo->descuenta_saldo,
            requiereTexto: (bool) $motivo->requiere_texto,
            activo: (bool) $motivo->activo,
        );
    }

    /**
     * @return list<MotivoTurnoData>
     */
    public function paraAccion(string $accion): array
    {
        $query = MotivoTurno::query()->activos()->ordenados();

        $accion === 'reprogramar'
            ? $query->paraReprogramar()
            : $query->paraCancelar();

        return $query->get()
            ->map(fn (MotivoTurno $motivo): MotivoTurnoData => $this->data($motivo))
            ->all();
    }
}
