<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\AccionCambioTurno;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\BitacoraEntry;

/**
 * Presenta una entrada de bitácora como ítem de historial del turno.
 */
final class TurnoHistorialPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function presentar(BitacoraEntry $entry): array
    {
        $antes = $entry->datos_anteriores ?? [];
        $despues = $entry->datos_nuevos ?? [];
        $accion = AccionCambioTurno::tryFrom((string) $entry->accion);
        $anterior = $this->estado($antes['estado'] ?? null);
        $nuevo = $this->estado($despues['estado'] ?? null);
        $vinculado = $despues['turno_nuevo_id'] ?? $despues['turno_origen_id'] ?? null;

        return [
            'id' => (int) $entry->id,
            'accion' => (string) $entry->accion,
            'accion_label' => $accion?->label() ?? (string) $entry->accion,
            'fecha' => $entry->created_at?->toIso8601String(),
            'usuario' => $entry->usuario?->name,
            'estado_anterior' => $anterior?->value,
            'estado_anterior_label' => $anterior?->label(),
            'estado_nuevo' => $nuevo?->value,
            'estado_nuevo_label' => $nuevo?->label(),
            'motivo_codigo' => $despues['motivo_codigo'] ?? null,
            'motivo_nombre' => $despues['motivo_nombre'] ?? null,
            'motivo_texto' => $despues['motivo_texto'] ?? $entry->motivo,
            'turno_vinculado_id' => is_numeric($vinculado) ? (int) $vinculado : null,
            'consume_saldo' => $despues['consume_saldo'] ?? null,
            'advertencias' => $despues['advertencias'] ?? [],
            'lote_id' => $despues['lote_id'] ?? null,
            'critico' => (bool) $entry->critico,
            'origen' => $despues['origen'] ?? ($accion === AccionCambioTurno::CREAR_POR_REPROGRAMACION ? 'sistema' : 'manual'),
        ];
    }

    private function estado(mixed $value): ?EstadoTurno
    {
        return is_string($value) ? EstadoTurno::tryFrom($value) : null;
    }
}
