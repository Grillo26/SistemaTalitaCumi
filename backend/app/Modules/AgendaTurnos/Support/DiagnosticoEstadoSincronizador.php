<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Events\EstadoGeneralDiagnosticoCambiado;
use App\Modules\AgendaTurnos\Rules\EstadoGeneralDiagnosticoRule;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionContract;

/**
 * 07.05/RF-005 / 07.05/RF-009 / 07.05/RN-004 / 07.05/RN-007.
 *
 * Recalcula `estado_general` en la misma transacción del cambio del turno.
 * Es fail-closed: si la lectura o la escritura fallan, la operación que
 * originó el cambio se revierte. Solo emite bitácora cuando el estado cambió.
 */
final class DiagnosticoEstadoSincronizador
{
    public function __construct(
        private readonly DiagnosticoAtencionContract $diagnosticos,
        private readonly EstadoGeneralDiagnosticoRule $rule,
        private readonly DiagnosticoTurnosResolver $turnos,
    ) {}

    public function sincronizar(
        int $diagnosticoId,
        ?User $actor = null,
        ?string $ip = null,
    ): ?EstadoGeneralDiagnostico {
        $resumen = $this->diagnosticos->resumen($diagnosticoId);

        if ($resumen === null) {
            return null;
        }

        if ($resumen->estadoGeneral === EstadoGeneralDiagnostico::CANCELADO->value) {
            return EstadoGeneralDiagnostico::CANCELADO;
        }

        $turnos = $this->turnos->delDiagnostico($diagnosticoId);
        $resultado = $this->rule->evaluar(
            $resumen->requisitos,
            $this->turnos->paraRegla($turnos),
            $this->turnos->especialidadesPorProfesional($resumen),
        );

        $anterior = EstadoGeneralDiagnostico::tryFrom($resumen->estadoGeneral)
            ?? EstadoGeneralDiagnostico::INCOMPLETO;

        if ($anterior === $resultado->estado) {
            return $resultado->estado;
        }

        $this->diagnosticos->actualizarEstadoGeneral($diagnosticoId, $resultado->estado->value);

        event(new EstadoGeneralDiagnosticoCambiado(
            diagnosticoId: $diagnosticoId,
            sucursalId: $resumen->sucursalId,
            estadoAnterior: $anterior,
            estadoNuevo: $resultado->estado,
            faltantes: $resultado->faltantes,
            actorId: $actor === null ? null : (int) $actor->id,
            ip: $ip,
            motivo: null,
        ));

        return $resultado->estado;
    }
}
