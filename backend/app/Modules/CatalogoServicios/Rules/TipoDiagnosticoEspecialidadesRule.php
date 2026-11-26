<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoCantidadInvalidaException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoEspecialidadDuplicadaException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoSinEspecialidadesException;

/**
 * Requisitos de especialidades (RN-D03 / RN-D04 / RF-D02).
 *
 * La Action valida después que las especialidades existan y estén activas;
 * la regla solo normaliza y valida la estructura.
 */
final class TipoDiagnosticoEspecialidadesRule
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{especialidad_id: int, cantidad_minima: int}>
     */
    public static function normalizeAndValidate(array $rows): array
    {
        if ($rows === []) {
            throw new TipoDiagnosticoSinEspecialidadesException;
        }

        $normalized = [];
        $seen = [];

        foreach ($rows as $row) {
            $especialidadId = (int) ($row['especialidad_id'] ?? 0);
            $cantidad = (int) ($row['cantidad_minima'] ?? 0);

            if ($especialidadId <= 0) {
                throw new TipoDiagnosticoSinEspecialidadesException;
            }

            if ($cantidad < 1) {
                throw new TipoDiagnosticoCantidadInvalidaException($especialidadId);
            }

            if (in_array($especialidadId, $seen, true)) {
                throw new TipoDiagnosticoEspecialidadDuplicadaException($especialidadId);
            }

            $seen[] = $especialidadId;
            $normalized[] = [
                'especialidad_id' => $especialidadId,
                'cantidad_minima' => $cantidad,
            ];
        }

        return $normalized;
    }
}
