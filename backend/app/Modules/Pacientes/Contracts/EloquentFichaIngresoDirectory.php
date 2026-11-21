<?php

namespace App\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Models\FichaIngreso;

/**
 * Implementación Eloquent del directorio de fichas de ingreso.
 */
final class EloquentFichaIngresoDirectory implements FichaIngresoDirectoryContract
{
    public function existe(int $pacienteId): bool
    {
        return FichaIngreso::query()
            ->vigente()
            ->where('paciente_id', $pacienteId)
            ->exists();
    }

    public function resumen(int $pacienteId): ?FichaIngresoResumen
    {
        $ficha = $this->vigente($pacienteId);

        if ($ficha === null) {
            return null;
        }

        return new FichaIngresoResumen(
            id: (int) $ficha->id,
            pacienteId: (int) $ficha->paciente_id,
            estado: $ficha->estado,
            fechaIngreso: $ficha->fecha_ingreso->toDateString(),
            sucursalIngresoId: (int) $ficha->sucursal_ingreso_id,
            version: (int) $ficha->version,
        );
    }

    public function tieneFichaCompleta(int $pacienteId): bool
    {
        return $this->vigente($pacienteId)?->estado->value === 'completa';
    }

    public function fichaVigenteId(int $pacienteId): ?int
    {
        $id = $this->vigente($pacienteId)?->id;

        return $id === null ? null : (int) $id;
    }

    private function vigente(int $pacienteId): ?FichaIngreso
    {
        return FichaIngreso::query()
            ->vigente()
            ->where('paciente_id', $pacienteId)
            ->latest('fecha_ingreso')
            ->latest('id')
            ->first();
    }
}
