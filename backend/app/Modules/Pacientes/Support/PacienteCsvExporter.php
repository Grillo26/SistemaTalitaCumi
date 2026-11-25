<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Pacientes\Models\Paciente;
use Illuminate\Database\Eloquent\Builder;

/**
 * Exportación CSV compatible con Excel (04.04/RF-013).
 *
 * Columnas mínimas: sin teléfonos, tutores, alergias, condiciones ni
 * observaciones. Usa BOM y punto y coma para Excel en español.
 */
final class PacienteCsvExporter
{
    public const DELIMITER = ';';

    public function count(Builder $query): int
    {
        return (clone $query)->count();
    }

    /**
     * @param  resource  $handle
     */
    public function write(Builder $query, User $actor, mixed $handle): void
    {
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [
            'ID',
            'Código',
            'Nombre completo',
            'Documento',
            'Fecha de nacimiento',
            'Sexo',
            'Estado',
            'Sucursal de registro',
        ], self::DELIMITER, '"', '');

        foreach ($query->lazy(200) as $paciente) {
            /** @var Paciente $paciente */
            fputcsv($handle, [
                (int) $paciente->id,
                $this->safe($paciente->codigo),
                $this->safe($paciente->nombre_completo),
                $this->safe($paciente->ci),
                $paciente->fecha_nacimiento->toDateString(),
                $paciente->sexo->label(),
                $paciente->estado->label(),
                $this->safe($paciente->sucursalRegistro?->nombre),
            ], self::DELIMITER, '"', '');
        }
    }

    /**
     * Evita que Excel interprete texto como fórmula.
     */
    private function safe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
