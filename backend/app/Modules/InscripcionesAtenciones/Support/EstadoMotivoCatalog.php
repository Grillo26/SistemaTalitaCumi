<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Enums\AccionEstado;
use App\Modules\InscripcionesAtenciones\Enums\MotivoBaja;
use App\Modules\InscripcionesAtenciones\Enums\MotivoBajaPaquete;
use App\Modules\InscripcionesAtenciones\Enums\MotivoPaciente;
use App\Modules\InscripcionesAtenciones\Enums\MotivoPausa;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Rules\MotivoEstadoObligatorioRule;

/**
 * Catálogo cerrado de motivos por acción y entidad.
 *
 * Se apoya en los Enums existentes de Salas/Paquetes y en los nuevos Enums
 * de pausa/retiro de paciente. Una tabla administrable queda como evolución
 * posterior para no cambiar el schema en esta entrega.
 */
final class EstadoMotivoCatalog
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function opciones(AccionEstado $accion, ?TipoEntidadVigencia $tipo = null): array
    {
        if ($accion === AccionEstado::REACTIVAR) {
            return [];
        }

        if ($accion === AccionEstado::PAUSAR) {
            return MotivoPausa::options();
        }

        return match ($tipo) {
            TipoEntidadVigencia::INSCRIPCION_SALA => MotivoBaja::options(),
            TipoEntidadVigencia::PAQUETE_SESIONES => MotivoBajaPaquete::options(),
            null => MotivoPaciente::options(),
        };
    }

    /**
     * @return list<string>
     */
    public static function codigos(AccionEstado $accion, ?TipoEntidadVigencia $tipo = null): array
    {
        return array_map(
            static fn (array $option): string => $option['value'],
            self::opciones($accion, $tipo),
        );
    }

    public static function assertValido(
        AccionEstado $accion,
        ?TipoEntidadVigencia $tipo,
        ?string $codigo,
        ?string $texto,
    ): void {
        MotivoEstadoObligatorioRule::assert(
            codigo: $codigo,
            texto: $texto,
            codigosValidos: self::codigos($accion, $tipo),
            requerido: $accion->requiereMotivo(),
        );
    }

    public static function label(
        AccionEstado $accion,
        ?TipoEntidadVigencia $tipo,
        ?string $codigo,
    ): ?string {
        if ($codigo === null || $codigo === '') {
            return null;
        }

        foreach (self::opciones($accion, $tipo) as $option) {
            if ($option['value'] === $codigo) {
                return $option['label'];
            }
        }

        return $codigo;
    }

    /**
     * Texto que se guarda en el histórico de paciente (que solo tiene texto).
     */
    public static function compose(
        AccionEstado $accion,
        ?TipoEntidadVigencia $tipo,
        ?string $codigo,
        ?string $texto,
    ): string {
        $label = self::label($accion, $tipo, $codigo);
        $texto = trim((string) $texto);

        if ($label === null) {
            return $texto;
        }

        return $texto === '' ? $label : $label.': '.$texto;
    }
}
