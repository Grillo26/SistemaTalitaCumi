<?php

namespace App\Modules\AgendaTurnos\Queries\Cambios;

use App\Modules\AgendaTurnos\Support\MotivoTurnoResolver;

/**
 * [Reprogramación y Cancelación] RF-003 / RNF-011.
 */
final class ListMotivosTurno
{
    public function __construct(private readonly MotivoTurnoResolver $motivos) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(string $accion): array
    {
        return array_map(
            static fn ($motivo): array => [
                'id' => $motivo->id,
                'codigo' => $motivo->codigo,
                'nombre' => $motivo->nombre,
                'origen' => $motivo->origen?->value,
                'origen_label' => $motivo->origen?->label(),
                'descuenta_saldo' => $motivo->descuentaSaldo,
                'requiere_texto' => $motivo->requiereTexto,
            ],
            $this->motivos->paraAccion($accion),
        );
    }
}
