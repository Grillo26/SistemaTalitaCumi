<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use Carbon\CarbonImmutable;

/**
 * Cálculos puros de fechas de paquetes.
 */
final class PaquetePeriod
{
    public static function today(): string
    {
        return now()->toDateString();
    }

    public static function nextStart(string $previousEnd): string
    {
        return CarbonImmutable::parse($previousEnd)->addDay()->toDateString();
    }

    /**
     * Conserva la duración inclusiva del período anterior.
     */
    public static function sameDurationEnd(
        string $newStart,
        string $previousStart,
        string $previousEnd,
    ): string {
        $inicio = CarbonImmutable::parse($previousStart);
        $fin = CarbonImmutable::parse($previousEnd);
        $dias = $inicio->diffInDays($fin) + 1;

        return CarbonImmutable::parse($newStart)->addDays($dias - 1)->toDateString();
    }
}
