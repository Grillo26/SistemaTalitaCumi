<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteBatchContract;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteResumen;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosContract;
use Illuminate\Support\Facades\DB;

/**
 * 07.04/RF-002 / 06.02 — lectura batch de consumo de paquetes.
 */
final class EloquentConsumoPaqueteBatch implements ConsumoPaqueteBatchContract
{
    public function __construct(private readonly OrigenTurnosContract $origenes) {}

    public function disponible(): bool
    {
        return true;
    }

    /**
     * @param  list<int>  $paqueteIds
     * @return array<int, ConsumoPaqueteResumen>
     */
    public function resumenes(array $paqueteIds): array
    {
        $ids = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            $paqueteIds,
        )));

        if ($ids === []) {
            return [];
        }

        $filas = Turno::query()
            ->whereIn('paquete_sesiones_id', $ids)
            ->where('estado', '!=', EstadoTurno::REPROGRAMADO->value)
            ->select([
                'paquete_sesiones_id',
                'estado',
                DB::raw('COUNT(*) AS total'),
            ])
            ->groupBy('paquete_sesiones_id', 'estado')
            ->get();

        $conteos = [];

        foreach ($filas as $fila) {
            $id = (int) $fila->paquete_sesiones_id;
            $conteos[$id][(string) $fila->estado] = (int) $fila->total;
        }

        $resultado = [];

        foreach ($ids as $id) {
            $origen = $this->origenes->obtener('paquete_sesiones', $id);

            $resultado[$id] = new ConsumoPaqueteResumen(
                pactadas: (int) ($origen?->cantidadSesiones ?? 0),
                realizadas: (int) ($conteos[$id][EstadoTurno::ASISTIO->value] ?? 0),
                faltadas: (int) ($conteos[$id][EstadoTurno::FALTO->value] ?? 0),
                canceladas: (int) ($conteos[$id][EstadoTurno::CANCELADO->value] ?? 0),
            );
        }

        return $resultado;
    }
}
