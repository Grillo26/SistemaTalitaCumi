<?php

namespace App\Modules\AgendaTurnos\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Advisory locks transaccionales y detección de violaciones de disponibilidad.
 *
 * En SQLite (suite de tests) los locks no aplican; la protección real es
 * PostgreSQL. Las violaciones 23P01/23505 se mapean a un error de dominio.
 */
final class TurnoLock
{
    public function porPacienteYProfesional(
        int $pacienteId,
        ?int $profesionalId,
        string $fecha,
    ): void {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $keys = [$this->key('paciente', $pacienteId, $fecha)];

        if ($profesionalId !== null) {
            $keys[] = $this->key('profesional', $profesionalId, $fecha);
        }

        sort($keys);

        foreach (array_unique($keys) as $key) {
            DB::select('SELECT pg_advisory_xact_lock(?)', [$key]);
        }
    }

    public function esViolacionDeDisponibilidad(QueryException $exception): bool
    {
        $state = $exception->errorInfo[0] ?? null;

        return in_array($state, ['23P01', '23505'], true);
    }

    private function key(string $tipo, int $id, string $fecha): int
    {
        return (int) hexdec(substr(hash('sha256', "turno:{$tipo}:{$id}:{$fecha}"), 0, 15));
    }
}
