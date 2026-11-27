<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\EfectoSaldoRule;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteContract;
use Throwable;

/**
 * Efecto en saldo de una cancelación.
 *
 * Solo muestra números si el Contract de consumo está disponible. Si no,
 * devuelve `disponible=false` y la UI muestra “Efecto en saldo no disponible”.
 * El snapshot `consume_saldo` se escribe igual en el turno.
 */
final class SaldoPaqueteResolver
{
    public function __construct(
        private readonly ConsumoPaqueteContract $consumo,
        private readonly EfectoSaldoRule $efecto,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function paraPaquete(?int $paqueteId, bool $consumira): array
    {
        if ($paqueteId === null) {
            return [
                'aplicable' => false,
                'disponible' => false,
                'consume_saldo' => $consumira,
                'mensaje' => 'El turno no pertenece a un paquete; no hay saldo de sesiones que calcular.',
            ];
        }

        if (! $this->consumo->disponible()) {
            return [
                'aplicable' => true,
                'disponible' => false,
                'consume_saldo' => $consumira,
                'mensaje' => 'Efecto en saldo no disponible.',
            ];
        }

        try {
            $resumen = $this->consumo->resumen($paqueteId);
        } catch (Throwable) {
            return [
                'aplicable' => true,
                'disponible' => false,
                'consume_saldo' => $consumira,
                'mensaje' => 'Efecto en saldo no disponible.',
            ];
        }

        $consumidas = Turno::query()
            ->where('paquete_sesiones_id', $paqueteId)
            ->where('estado', 'cancelado')
            ->where('consume_saldo', true)
            ->count();

        $saldoActual = max(
            0,
            $resumen->pactadas - $resumen->realizadas - $resumen->faltadas - $consumidas,
        );

        return [
            'aplicable' => true,
            'disponible' => true,
            'consume_saldo' => $consumira,
            'pactadas' => $resumen->pactadas,
            'realizadas' => $resumen->realizadas,
            'faltadas' => $resumen->faltadas,
            'canceladas' => $resumen->canceladas,
            'canceladas_consumen' => $consumidas,
            'saldo_actual' => $saldoActual,
            'saldo_resultante' => $consumira ? max(0, $saldoActual - 1) : $saldoActual,
            'mensaje' => $this->efecto->descripcion($consumira),
        ];
    }
}
