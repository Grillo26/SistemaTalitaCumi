<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Models\Turno;

/**
 * Inserción de turnos en lote. Es una clase (no final) para que los tests de
 * transaccionalidad puedan sustituirla y provocar un fallo a mitad.
 */
class TurnoBatchWriter
{
    /**
     * @param  list<array<string, mixed>>  $filas
     */
    public function insertar(array $filas): void
    {
        foreach (array_chunk($filas, 200) as $chunk) {
            Turno::query()->insert($chunk);
        }
    }
}
