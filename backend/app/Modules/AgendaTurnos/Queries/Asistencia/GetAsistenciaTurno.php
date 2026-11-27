<?php

namespace App\Modules\AgendaTurnos\Queries\Asistencia;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Support\AsistenciaAcceso;
use App\Modules\AgendaTurnos\Support\AsistenciaCalculadora;
use App\Modules\AgendaTurnos\Support\AsistenciaTurnosPermission;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\AgendaTurnos\Support\IndicadorAsistenciaResolver;
use App\Modules\AgendaTurnos\Support\SaldoAsistenciaResolver;

/**
 * 07.04/RF-001, 07.04/RF-004, 07.04/RF-005, 07.04/RF-013, 07.04/RF-015.
 *
 * Estado actual de la asistencia, capacidades calculadas en backend y
 * efecto estimado de una corrección. No escribe nada.
 */
final class GetAsistenciaTurno
{
    public function __construct(
        private readonly AsistenciaAcceso $acceso,
        private readonly AsistenciaCalculadora $calculadora,
        private readonly SaldoAsistenciaResolver $saldo,
        private readonly IndicadorAsistenciaResolver $indicadores,
        private readonly DirectoriosAgenda $nombres,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $turnoId, User $actor): array
    {
        $turno = $this->acceso->turno($turnoId, $actor);
        $datos = $this->calculadora->datos($turno);
        $ventana = $datos['ventana'];
        $destino = $this->calculadora->destinoCorreccion($turno);
        $origen = $this->calculadora->origen($turno);

        $puedeRegistrar = $actor->can(AsistenciaTurnosPermission::REGISTRAR)
            && $turno->estado === EstadoTurno::PROGRAMADO
            && $datos['errores_registro'] === [];

        $puedeCorregir = $actor->can(AsistenciaTurnosPermission::REGISTRAR)
            && $destino !== null
            && ($ventana['dentro'] || $actor->can(AsistenciaTurnosPermission::CORREGIR_FUERA_VENTANA));

        $fueraVentana = $destino !== null && ! $ventana['dentro'];
        $efecto = $destino === null
            ? null
            : [
                'saldo' => $this->saldo->paraTurno($turno, true),
                'indicadores' => $this->indicadores->paraTurno($turno, $destino, true, $origen),
            ];

        $mensaje = null;

        if ($turno->estado === EstadoTurno::CANCELADO) {
            $mensaje = 'El turno está cancelado y no admite asistencia.';
        } elseif ($turno->estado === EstadoTurno::REPROGRAMADO) {
            $mensaje = 'El turno fue reprogramado; gestioná el turno vigente.';
        } elseif ($turno->estado === EstadoTurno::PROGRAMADO && $datos['errores_registro'] !== []) {
            $mensaje = $datos['errores_registro'][0];
        } elseif ($fueraVentana && ! $actor->can(AsistenciaTurnosPermission::CORREGIR_FUERA_VENTANA)) {
            $mensaje = 'La ventana de edición venció. Contactá a Administración para corregir la asistencia.';
        }

        return [
            'turno' => [
                ...$datos,
                'paciente' => [
                    'id' => (int) $turno->paciente_id,
                    'nombre' => $this->nombres->paciente((int) $turno->paciente_id),
                ],
                'servicio' => [
                    'id' => (int) $turno->servicio_id,
                    'nombre' => $this->nombres->servicio((int) $turno->servicio_id),
                ],
                'sala' => $turno->sala_id === null ? null : [
                    'id' => (int) $turno->sala_id,
                    'nombre' => $this->nombres->sala((int) $turno->sala_id),
                ],
                'profesional' => $turno->profesional_id === null ? null : [
                    'id' => (int) $turno->profesional_id,
                    'nombre' => $this->nombres->profesional((int) $turno->profesional_id),
                ],
                'usuario_asistencia' => $turno->usuarioAsistencia?->name,
            ],
            'ventana' => $ventana,
            'correccion' => [
                'destino' => $destino?->value,
                'destino_label' => $destino?->label(),
                'fuera_ventana' => $fueraVentana,
                'requiere_motivo' => $fueraVentana,
            ],
            'efecto' => $efecto,
            'mensaje' => $mensaje,
            'can' => [
                'ver' => $actor->can(AsistenciaTurnosPermission::VER),
                'registrar' => $puedeRegistrar,
                'corregir' => $puedeCorregir,
                'corregir_fuera_ventana' => $actor->can(AsistenciaTurnosPermission::CORREGIR_FUERA_VENTANA),
                'ver_historial' => $actor->can('turno.historial'),
            ],
            'acciones' => $this->acciones($puedeRegistrar, $puedeCorregir),
        ];
    }

    /**
     * @return list<array{codigo: string, etiqueta: string}>
     */
    private function acciones(bool $puedeRegistrar, bool $puedeCorregir): array
    {
        $acciones = [];

        if ($puedeRegistrar) {
            $acciones[] = ['codigo' => 'registrar_asistio', 'etiqueta' => 'Asistió'];
            $acciones[] = ['codigo' => 'registrar_falto', 'etiqueta' => 'Faltó'];
        }

        if ($puedeCorregir) {
            $acciones[] = ['codigo' => 'corregir_asistencia', 'etiqueta' => 'Corregir asistencia'];
        }

        return $acciones;
    }
}
