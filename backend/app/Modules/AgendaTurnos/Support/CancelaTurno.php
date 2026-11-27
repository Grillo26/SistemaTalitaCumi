<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Models\User;
use App\Modules\AgendaTurnos\Models\Turno;

/**
 * Aplica los campos de cancelación sobre un turno. No abre transacciones ni
 * dispara eventos: eso lo hace la Action que la invoca.
 */
final class CancelaTurno
{
    public function aplicar(
        Turno $turno,
        MotivoTurnoData $motivo,
        ?string $motivoTexto,
        bool $consumeSaldo,
        User $actor,
        ?string $loteId = null,
        bool $masivo = false,
    ): void {
        $texto = $motivo->nombre;
        $detalle = trim((string) $motivoTexto);

        if ($detalle !== '') {
            $texto .= ': '.$detalle;
        }

        $turno->forceFill([
            'estado' => 'cancelado',
            'cancelacion_origen' => 'manual',
            'motivo_turno_id' => $motivo->id,
            'motivo_detalle' => $detalle === '' ? null : $detalle,
            'motivo_cancelacion' => $texto,
            'consume_saldo' => $consumeSaldo,
            'cancelado_en' => now(config('app.timezone')),
            'cancelado_por_usuario_id' => $actor->id,
        ])->save();
    }
}
