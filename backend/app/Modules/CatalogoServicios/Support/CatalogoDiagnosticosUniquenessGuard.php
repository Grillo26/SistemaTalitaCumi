<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoNombreDuplicadoException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoServicioDuplicadoException;
use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Rules\TipoDiagnosticoNombreUnicoRule;
use Illuminate\Database\QueryException;

/**
 * Unicidad de nombre y servicio por tipo de diagnóstico.
 */
final class CatalogoDiagnosticosUniquenessGuard
{
    public function assertNombreAvailable(string $nombre, ?int $ignoreId = null): void
    {
        $candidate = NombreNormalizadoRule::fold($nombre);

        $existing = TipoDiagnostico::query()
            ->when($ignoreId !== null, static fn ($query) => $query->whereKeyNot($ignoreId))
            ->pluck('nombre')
            ->map(static fn (string $name): string => NombreNormalizadoRule::fold($name))
            ->all();

        TipoDiagnosticoNombreUnicoRule::assertAvailable($candidate, $existing);
    }

    public function assertServicioAvailable(int $servicioId, ?int $ignoreId = null): void
    {
        $query = TipoDiagnostico::query()->where('servicio_id', $servicioId);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $existing = $query->with('servicio')->first();

        if ($existing !== null) {
            throw new TipoDiagnosticoServicioDuplicadoException(
                $existing->servicio?->nombre ?? (string) $servicioId,
            );
        }
    }

    public function translateUniqueViolation(QueryException $exception): void
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'tipo_diagnostico_nombre_normalizado_uk')
            || str_contains($message, 'tipo_diagnostico_nombre_unique')) {
            throw new TipoDiagnosticoNombreDuplicadoException;
        }

        if (str_contains($message, 'tipo_diagnostico_servicio_id_unique')) {
            throw new TipoDiagnosticoServicioDuplicadoException('');
        }
    }
}
