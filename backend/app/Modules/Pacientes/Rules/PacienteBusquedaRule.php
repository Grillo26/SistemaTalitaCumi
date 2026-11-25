<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\PacienteSearchTermTooShortException;
use App\Modules\Pacientes\Support\PacienteNormalizer;

/**
 * Reglas de la búsqueda de pacientes (04.04/RF-001, 04.04/RF-002, 04.04/RF-003).
 *
 * La normalización es pura: no toca base de datos ni permisos. La decisión de
 * buscar por teléfono del tutor se toma en la Query según el permiso.
 */
final class PacienteBusquedaRule
{
    public const MIN_LENGTH = 2;

    /**
     * @var list<string>
     */
    public const SORTS = [
        'apellido',
        'nombre_completo',
        'codigo',
        'fecha_nacimiento',
        'estado',
        'updated_at',
    ];

    public static function term(?string $search): string
    {
        return PacienteNormalizer::key($search);
    }

    /**
     * @return list<string>
     */
    public static function tokens(string $term): array
    {
        return array_values(array_filter(
            explode(' ', $term),
            static fn (string $token): bool => $token !== '',
        ));
    }

    public static function tutorPhone(?string $search): ?string
    {
        $phone = TutorPhoneRule::normalize($search);

        return $phone !== null && strlen($phone) === 8 ? $phone : null;
    }

    public static function isAllowedSort(string $sort): bool
    {
        return in_array($sort, self::SORTS, true);
    }

    public static function assertValid(?string $search): void
    {
        $term = trim((string) $search);

        if ($term !== '' && mb_strlen($term) < self::MIN_LENGTH) {
            throw new PacienteSearchTermTooShortException;
        }
    }
}
