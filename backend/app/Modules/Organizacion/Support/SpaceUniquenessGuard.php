<?php

namespace App\Modules\Organizacion\Support;

use App\Modules\Organizacion\Exceptions\DuplicateAmbienteException;
use App\Modules\Organizacion\Exceptions\DuplicateSalaException;
use App\Modules\Organizacion\Exceptions\DuplicateTipoSalaException;
use App\Modules\Organizacion\Exceptions\DuplicateTurnoException;
use App\Modules\Organizacion\Models\Ambiente;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Models\TurnoCatalogo;
use Illuminate\Database\QueryException;

/**
 * Comprobaciones de unicidad contra la base de datos.
 *
 * La normalización de texto vive en SpaceNameNormalizationRule. Los índices
 * únicos de BD son el respaldo definitivo.
 */
final class SpaceUniquenessGuard
{
    public function assertTipoSalaNameAvailable(string $normalizedName, ?int $ignoreId = null): void
    {
        $query = TipoSala::query()->whereRaw('lower(nombre) = ?', [mb_strtolower($normalizedName)]);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw new DuplicateTipoSalaException;
        }
    }

    public function assertTurnoNameAvailable(string $normalizedName, ?int $ignoreId = null): void
    {
        $query = TurnoCatalogo::query()->whereRaw('lower(nombre) = ?', [mb_strtolower($normalizedName)]);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw new DuplicateTurnoException;
        }
    }

    public function assertSalaCombinationAvailable(
        int $sucursalId,
        int $tipoSalaId,
        int $turnoCatalogoId,
        ?int $ignoreId = null,
    ): void {
        $query = Sala::query()
            ->with(['tipoSala', 'turnoCatalogo', 'sucursal'])
            ->where('sucursal_id', $sucursalId)
            ->where('tipo_sala_id', $tipoSalaId)
            ->where('turno_catalogo_id', $turnoCatalogoId);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $conflict = $query->first();

        if ($conflict !== null) {
            throw new DuplicateSalaException([
                'id' => $conflict->id,
                'nombre' => $conflict->nombre,
                'tipo_sala' => $conflict->tipoSala?->nombre ?? '',
                'turno' => $conflict->turnoCatalogo?->nombre ?? '',
                'sucursal' => $conflict->sucursal?->nombre ?? '',
            ]);
        }
    }

    public function assertAmbienteNameAvailable(
        int $sucursalId,
        string $normalizedName,
        ?int $ignoreId = null,
    ): void {
        $query = Ambiente::query()
            ->with('sucursal')
            ->where('sucursal_id', $sucursalId)
            ->whereRaw('lower(nombre) = ?', [mb_strtolower($normalizedName)]);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $conflict = $query->first();

        if ($conflict !== null) {
            throw new DuplicateAmbienteException([
                'id' => $conflict->id,
                'nombre' => $conflict->nombre,
                'sucursal' => $conflict->sucursal?->nombre ?? '',
            ]);
        }
    }

    public function translateUniqueViolation(
        QueryException $exception,
        ?int $sucursalId = null,
        ?int $tipoSalaId = null,
        ?int $turnoCatalogoId = null,
    ): void {
        $message = $exception->getMessage();

        if (str_contains($message, 'tipo_sala_nombre_normalizado_uk')) {
            throw new DuplicateTipoSalaException;
        }

        if (str_contains($message, 'turno_catalogo_nombre_normalizado_uk')) {
            throw new DuplicateTurnoException;
        }

        if (str_contains($message, 'ambiente_sucursal_nombre_normalizado_uk')) {
            throw new DuplicateAmbienteException;
        }

        if (str_contains($message, 'sala_identidad_unica')
            && $sucursalId !== null
            && $tipoSalaId !== null
            && $turnoCatalogoId !== null) {
            $this->assertSalaCombinationAvailable($sucursalId, $tipoSalaId, $turnoCatalogoId);
        }
    }
}
