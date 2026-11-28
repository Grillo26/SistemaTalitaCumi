<?php

namespace App\Modules\AgendaTurnos\Rules;

use Carbon\CarbonImmutable;

/**
 * 07.04/RF-004, 07.04/RF-005, 07.04/RN-004, 07.04/CA-005, 07.04/CA-006.
 *
 * La ventana corre desde el primer registro (`asistencia_registrada_en`).
 * `0` significa que no hay ventana: toda corrección requiere permiso
 * especial y motivo.
 */
final class VentanaEdicionAsistenciaRule
{
    /**
     * @return array{dentro: bool, horas: int, vence_en: ?string, motivo: string}
     */
    public function evaluar(
        ?CarbonImmutable $registradaEn,
        CarbonImmutable $ahora,
        int $horas,
    ): array {
        $horas = max(0, $horas);

        if ($registradaEn === null) {
            return [
                'dentro' => false,
                'horas' => $horas,
                'vence_en' => null,
                'motivo' => 'sin_registro',
            ];
        }

        if ($horas === 0) {
            return [
                'dentro' => false,
                'horas' => 0,
                'vence_en' => null,
                'motivo' => 'ventana_cerrada',
            ];
        }

        $vence = $registradaEn->addHours($horas);

        return [
            'dentro' => $ahora->lessThanOrEqualTo($vence),
            'horas' => $horas,
            'vence_en' => $vence->toIso8601String(),
            'motivo' => $ahora->lessThanOrEqualTo($vence) ? 'dentro' : 'vencida',
        ];
    }

    public function dentro(
        ?CarbonImmutable $registradaEn,
        CarbonImmutable $ahora,
        int $horas,
    ): bool {
        return $this->evaluar($registradaEn, $ahora, $horas)['dentro'];
    }
}
