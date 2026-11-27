<?php

namespace App\Modules\AgendaTurnos\Queries\Asistencia;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\AsistenciaClock;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use Illuminate\Support\Facades\DB;

/**
 * 07.04/RF-014, 07.04/RN-010, 07.04/CA-009.
 *
 * Resumen diario informativo por sucursal. Se calcula en demanda; no hay
 * jobs, cierres ni notificaciones.
 */
final class GetResumenDiarioAsistencia
{
    public function __construct(
        private readonly AgendaAccessScope $scope,
        private readonly DirectoriosAgenda $nombres,
        private readonly AsistenciaClock $clock,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $fecha, int $sucursalId, User $actor): array
    {
        $this->scope->assertSucursal($actor, $sucursalId);
        $sucursal = $this->scope->resumenSucursal($sucursalId);

        $conteos = Turno::query()
            ->where('sucursal_id', $sucursalId)
            ->where('fecha', $fecha)
            ->select(['estado', DB::raw('COUNT(*) AS total')])
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $porEstado = [];

        foreach ($conteos as $estado => $total) {
            $porEstado[(string) $estado] = (int) $total;
        }

        $resumen = [
            'total' => array_sum($porEstado),
            'atendidos' => $porEstado[EstadoTurno::ASISTIO->value] ?? 0,
            'faltados' => $porEstado[EstadoTurno::FALTO->value] ?? 0,
            'cancelados' => $porEstado[EstadoTurno::CANCELADO->value] ?? 0,
            'pendientes' => $porEstado[EstadoTurno::PROGRAMADO->value] ?? 0,
            'reprogramados' => $porEstado[EstadoTurno::REPROGRAMADO->value] ?? 0,
        ];

        return [
            'fecha' => $fecha,
            'sucursal' => [
                'id' => $sucursalId,
                'nombre' => $sucursal->nombre,
            ],
            'zona_horaria' => $this->clock->zonaHoraria(),
            'es_hoy' => $fecha === $this->clock->hoy(),
            'conteos' => $resumen,
            'por_sala' => $this->porSala($sucursalId, $fecha),
            'mensaje' => $resumen['total'] === 0
                ? 'No hay turnos registrados para la fecha y sucursal seleccionadas.'
                : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function porSala(int $sucursalId, string $fecha): array
    {
        $filas = Turno::query()
            ->where('sucursal_id', $sucursalId)
            ->where('fecha', $fecha)
            ->whereNotNull('sala_id')
            ->select([
                'sala_id',
                'estado',
                DB::raw('COUNT(*) AS total'),
            ])
            ->groupBy('sala_id', 'estado')
            ->get();

        $agrupado = [];

        foreach ($filas as $fila) {
            $salaId = (int) $fila->sala_id;
            $estado = $fila->estado instanceof EstadoTurno
                ? $fila->estado->value
                : (string) $fila->estado;
            $agrupado[$salaId][$estado] = (int) $fila->total;
        }

        $nombres = $this->nombres->salas(array_keys($agrupado));
        $resultado = [];

        foreach ($agrupado as $salaId => $estados) {
            $resultado[] = [
                'sala_id' => $salaId,
                'sala' => $nombres[$salaId] ?? DirectoriosAgenda::NO_DISPONIBLE,
                'total' => array_sum($estados),
                'atendidos' => $estados[EstadoTurno::ASISTIO->value] ?? 0,
                'faltados' => $estados[EstadoTurno::FALTO->value] ?? 0,
                'cancelados' => $estados[EstadoTurno::CANCELADO->value] ?? 0,
                'pendientes' => $estados[EstadoTurno::PROGRAMADO->value] ?? 0,
                'reprogramados' => $estados[EstadoTurno::REPROGRAMADO->value] ?? 0,
            ];
        }

        return $resultado;
    }
}
