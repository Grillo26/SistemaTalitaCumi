<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Enums\EstadoVigencia;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Rules\VigenciaClassifierRule;
use Carbon\CarbonImmutable;

/**
 * Presentación común del estado de vigencia.
 */
final class VigenciaPresenter
{
    /**
     * @param  array{retirada: bool, renovada: bool, fecha_fin: string}  $datos
     * @return array{estado: EstadoVigencia, dias: int, label: string}
     */
    public static function estado(array $datos, int $ventana, string $hoy): array
    {
        $estado = VigenciaClassifierRule::classify(
            fechaFin: $datos['fecha_fin'],
            hoy: $hoy,
            ventanaDias: $ventana,
            retirada: $datos['retirada'],
            renovada: $datos['renovada'],
        );

        return [
            'estado' => $estado,
            'dias' => VigenciaClassifierRule::diasHastaVencimiento($datos['fecha_fin'], $hoy),
            'label' => $estado->label(),
        ];
    }

    public static function tipo(TipoEntidadVigencia $tipo): string
    {
        return $tipo->label();
    }

    public static function hoy(): string
    {
        return CarbonImmutable::now(config('app.timezone'))->toDateString();
    }
}
