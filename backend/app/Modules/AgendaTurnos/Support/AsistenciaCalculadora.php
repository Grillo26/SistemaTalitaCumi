<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\TransicionAsistenciaRule;
use App\Modules\AgendaTurnos\Rules\TurnoAsistibleRule;
use App\Modules\AgendaTurnos\Rules\VentanaEdicionAsistenciaRule;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosContract;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosResumen;
use Throwable;

/**
 * 07.04 — cálculo compartido por Query y Actions.
 *
 * No escribe: carga el origen, evalúa elegibilidad, ventana y transición.
 * Las Actions vuelven a ejecutarlo bajo lock.
 */
final class AsistenciaCalculadora
{
    public function __construct(
        private readonly AsistenciaClock $clock,
        private readonly TurnoAsistibleRule $asistible,
        private readonly TransicionAsistenciaRule $transicion,
        private readonly VentanaEdicionAsistenciaRule $ventana,
        private readonly OrigenTurnosContract $origenes,
    ) {}

    public function tieneOrigen(Turno $turno): bool
    {
        return $turno->paquete_sesiones_id !== null
            || $turno->inscripcion_sala_id !== null;
    }

    public function esDiagnostico(Turno $turno): bool
    {
        return $turno->atencion_diagnostico_id !== null;
    }

    public function origen(Turno $turno): ?OrigenTurnosResumen
    {
        if ($turno->paquete_sesiones_id !== null) {
            return $this->buscar('paquete_sesiones', (int) $turno->paquete_sesiones_id);
        }

        if ($turno->inscripcion_sala_id !== null) {
            return $this->buscar('inscripcion_sala', (int) $turno->inscripcion_sala_id);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function erroresRegistro(Turno $turno): array
    {
        return $this->asistible->errores(
            $turno,
            $this->tieneOrigen($turno),
            $this->origen($turno),
            $this->clock->ahora(),
        );
    }

    /**
     * @return array{dentro: bool, horas: int, vence_en: ?string, motivo: string}
     */
    public function ventana(Turno $turno): array
    {
        return $this->ventana->evaluar(
            $this->clock->registradaEn($turno),
            $this->clock->ahora(),
            $this->clock->ventanaHoras(),
        );
    }

    public function destinoCorreccion(Turno $turno): ?EstadoTurno
    {
        return match ($turno->estado) {
            EstadoTurno::ASISTIO => EstadoTurno::FALTO,
            EstadoTurno::FALTO => EstadoTurno::ASISTIO,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public function erroresCorreccion(Turno $turno, EstadoTurno $destino): array
    {
        return $this->transicion->errores($turno->estado, $destino, true);
    }

    public function dentroDeVentana(Turno $turno): bool
    {
        return $this->ventana($turno)['dentro'];
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(Turno $turno): array
    {
        $origen = $this->origen($turno);

        return [
            'id' => (int) $turno->id,
            'fecha' => $turno->fecha->toDateString(),
            'hora_inicio' => $turno->hora_inicio,
            'hora_fin' => $turno->hora_fin,
            'estado' => $turno->estado->value,
            'estado_label' => $turno->estado->label(),
            'version' => (int) ($turno->version ?? 1),
            'asistencia_registrada_en' => $turno->asistencia_registrada_en?->toIso8601String(),
            'sucursal_id' => (int) $turno->sucursal_id,
            'paciente_id' => (int) $turno->paciente_id,
            'servicio_id' => (int) $turno->servicio_id,
            'profesional_id' => $turno->profesional_id === null
                ? null
                : (int) $turno->profesional_id,
            'sala_id' => $turno->sala_id === null ? null : (int) $turno->sala_id,
            'origen' => $origen === null ? null : [
                'tipo' => $origen->tipo,
                'id' => $origen->id,
                'estado_efectivo' => $origen->estadoEfectivo,
                'fecha_inicio' => $origen->fechaInicio,
                'fecha_fin' => $origen->fechaFin,
                'cantidad_sesiones' => $origen->cantidadSesiones,
            ],
            'es_diagnostico' => $this->esDiagnostico($turno),
            'ventana' => $this->ventana($turno),
            'errores_registro' => $turno->estado === EstadoTurno::PROGRAMADO
                ? $this->erroresRegistro($turno)
                : [],
            'destino_correccion' => $this->destinoCorreccion($turno)?->value,
        ];
    }

    private function buscar(string $tipo, int $id): ?OrigenTurnosResumen
    {
        try {
            return $this->origenes->obtener($tipo, $id);
        } catch (Throwable) {
            return null;
        }
    }
}
