<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Rules\SaldoSesionesRule;
use Throwable;

/**
 * 07.04/RF-002, 07.04/RF-013, 07.04/RN-002, 07.04/RN-009, 07.04/CA-002,
 * 07.04/CA-003, 07.04/CA-005.
 *
 * El saldo es derivado y pertenece a 06.02. Este resolver solo lee el
 * contrato de consumo; no escribe contadores ni tablas de Inscripciones.
 */
final class SaldoAsistenciaResolver
{
    public function __construct(private readonly ConsumoPaqueteContract $consumo) {}

    /**
     * @return array<string, mixed>
     */
    public function paraTurno(Turno $turno, bool $correccion): array
    {
        if ($turno->paquete_sesiones_id === null) {
            return [
                'aplicable' => false,
                'disponible' => false,
                'mensaje' => 'El turno no pertenece a un paquete; no hay saldo de sesiones que calcular.',
            ];
        }

        if (! $this->consumo->disponible()) {
            return [
                'aplicable' => true,
                'disponible' => false,
                'mensaje' => 'Efecto en saldo no disponible.',
            ];
        }

        try {
            $resumen = $this->consumo->resumen((int) $turno->paquete_sesiones_id);
        } catch (Throwable) {
            return [
                'aplicable' => true,
                'disponible' => false,
                'mensaje' => 'Efecto en saldo no disponible.',
            ];
        }

        $saldoActual = SaldoSesionesRule::calcular(
            $resumen->pactadas,
            $resumen->realizadas,
            $resumen->faltadas,
        );

        $saldoResultante = $correccion
            ? $saldoActual
            : max(0, $saldoActual - 1);

        $agotado = $saldoActual === 0
            || ($resumen->realizadas + $resumen->faltadas) >= $resumen->pactadas;

        return [
            'aplicable' => true,
            'disponible' => true,
            'pactadas' => $resumen->pactadas,
            'realizadas' => $resumen->realizadas,
            'faltadas' => $resumen->faltadas,
            'canceladas' => $resumen->canceladas,
            'saldo_actual' => $saldoActual,
            'saldo_resultante' => $saldoResultante,
            'saldo_agotado' => $agotado,
            'mensaje' => $agotado
                ? 'Saldo agotado; la asistencia real se registra igual.'
                : ($correccion
                    ? "La corrección no cambia el saldo neto del paquete ({$saldoActual} sesiones)."
                    : "El saldo del paquete pasará de {$saldoActual} a {$saldoResultante}."),
        ];
    }
}
