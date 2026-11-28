<?php

namespace App\Modules\AgendaTurnos\Rules;

/**
 * 07.04/RF-005, 07.04/RF-006, 07.04/RN-006.
 *
 * Fuera de la ventana el motivo es obligatorio; dentro es opcional.
 */
final class MotivoAsistenciaRule
{
    public const MAX_LENGTH = 500;

    /**
     * @return list<string>
     */
    public function errores(bool $fueraVentana, ?string $motivo): array
    {
        $motivo = trim((string) $motivo);

        if ($fueraVentana && $motivo === '') {
            return ['La corrección fuera de la ventana exige un motivo.'];
        }

        if (mb_strlen($motivo) > self::MAX_LENGTH) {
            return ['El motivo no puede superar los '.self::MAX_LENGTH.' caracteres.'];
        }

        return [];
    }

    public function normalizar(?string $motivo): ?string
    {
        $motivo = trim((string) $motivo);

        return $motivo === '' ? null : $motivo;
    }
}
