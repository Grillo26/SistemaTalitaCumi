<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\EstadoEfectivoInscripcionRule;
use App\Modules\InscripcionesAtenciones\Rules\RetiroEnCascadaRule;

/**
 * Resolución de estado efectivo para una fila concreta.
 */
final class EstadoInscripcionResolver
{
    public static function renovadaSala(InscripcionSala $inscripcion): bool
    {
        return $inscripcion->renovaciones()
            ->where('estado', '!=', 'retirada')
            ->exists();
    }

    public static function renovadaPaquete(PaqueteSesiones $paquete): bool
    {
        return $paquete->renovaciones()
            ->where('estado', '!=', 'retirado')
            ->exists();
    }

    public static function efectivoSala(InscripcionSala $inscripcion, ?string $hoy = null): EstadoInscripcion
    {
        return EstadoEfectivoInscripcionRule::effective(
            almacenado: EstadoInscripcion::fromSala($inscripcion->estado),
            fechaFin: $inscripcion->fecha_fin->toDateString(),
            hoy: $hoy ?? VigenciaPeriod::today(),
            renovada: self::renovadaSala($inscripcion),
        );
    }

    public static function efectivoPaquete(PaqueteSesiones $paquete, ?string $hoy = null): EstadoInscripcion
    {
        return EstadoEfectivoInscripcionRule::effective(
            almacenado: EstadoInscripcion::fromPaquete($paquete->estado),
            fechaFin: $paquete->fecha_fin->toDateString(),
            hoy: $hoy ?? VigenciaPeriod::today(),
            renovada: self::renovadaPaquete($paquete),
        );
    }

    /**
     * Inscripciones activas o en pausa que un retiro de paciente arrastra.
     *
     * @return list<array{tipo: TipoEntidadVigencia, id: int, sucursal_id: int, estado_efectivo: EstadoInscripcion}>
     */
    public static function arrastrablesDePaciente(int $pacienteId, ?string $hoy = null): array
    {
        $hoy ??= VigenciaPeriod::today();
        $resultado = [];

        $inscripciones = InscripcionSala::query()
            ->where('paciente_id', $pacienteId)
            ->whereIn('estado', ['activa', 'en_pausa'])
            ->get();

        foreach ($inscripciones as $inscripcion) {
            $efectivo = self::efectivoSala($inscripcion, $hoy);

            if (RetiroEnCascadaRule::arrastra($efectivo)) {
                $resultado[] = [
                    'tipo' => TipoEntidadVigencia::INSCRIPCION_SALA,
                    'id' => (int) $inscripcion->id,
                    'sucursal_id' => (int) $inscripcion->sucursal_id,
                    'estado_efectivo' => $efectivo,
                ];
            }
        }

        $paquetes = PaqueteSesiones::query()
            ->where('paciente_id', $pacienteId)
            ->whereIn('estado', ['activo', 'en_pausa'])
            ->get();

        foreach ($paquetes as $paquete) {
            $efectivo = self::efectivoPaquete($paquete, $hoy);

            if (RetiroEnCascadaRule::arrastra($efectivo)) {
                $resultado[] = [
                    'tipo' => TipoEntidadVigencia::PAQUETE_SESIONES,
                    'id' => (int) $paquete->id,
                    'sucursal_id' => (int) $paquete->sucursal_id,
                    'estado_efectivo' => $efectivo,
                ];
            }
        }

        return $resultado;
    }
}
