<?php

namespace App\Modules\AgendaTurnos\Support;

use Carbon\CarbonImmutable;

/**
 * Reloj del módulo 07 en America/La_Paz.
 *
 * Centraliza "hoy" y "ahora" para que las Rules reciban los valores y los
 * tests puedan fijarlos con Carbon::setTestNow.
 */
final class AgendaClock
{
    public function ahora(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'));
    }

    public function hoy(): string
    {
        return $this->ahora()->toDateString();
    }

    public function horaActual(): string
    {
        return $this->ahora()->format('H:i');
    }
}
