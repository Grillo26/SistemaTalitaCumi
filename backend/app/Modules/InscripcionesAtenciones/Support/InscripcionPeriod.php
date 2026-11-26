<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use Carbon\CarbonImmutable;

/**
 * Cálculos puros del período mensual (RN-001).
 */
final class InscripcionPeriod
{
    public static function maxEnd(string $start): string
    {
        return CarbonImmutable::parse($start)->addMonthNoOverflow()->toDateString();
    }

    public static function defaultEnd(string $start): string
    {
        return CarbonImmutable::parse($start)->addMonthNoOverflow()->subDay()->toDateString();
    }

    public static function nextStart(string $previousEnd): string
    {
        return CarbonImmutable::parse($previousEnd)->addDay()->toDateString();
    }

    /**
     * @return array{desde: string, hasta: string}
     */
    public static function monthRange(int $month, int $year): array
    {
        $start = CarbonImmutable::createFromDate($year, $month, 1, config('app.timezone'))
            ->startOfMonth();
        $end = $start->endOfMonth();

        return [
            'desde' => $start->toDateString(),
            'hasta' => $end->toDateString(),
        ];
    }

    public static function today(): string
    {
        return now()->toDateString();
    }
}
