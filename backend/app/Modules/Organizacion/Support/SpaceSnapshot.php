<?php

namespace App\Modules\Organizacion\Support;

use App\Modules\Organizacion\Models\Ambiente;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Models\TurnoCatalogo;

final class SpaceSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function fromTipoSala(TipoSala $tipoSala): array
    {
        return [
            'id' => $tipoSala->id,
            'nombre' => $tipoSala->nombre,
            'activo' => $tipoSala->activo,
            'version' => $tipoSala->version,
            'updated_at' => $tipoSala->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromTurno(TurnoCatalogo $turno): array
    {
        return [
            'id' => $turno->id,
            'nombre' => $turno->nombre,
            'orden' => $turno->orden,
            'activo' => $turno->activo,
            'version' => $turno->version,
            'updated_at' => $turno->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromSala(Sala $sala): array
    {
        $sala->loadMissing(['sucursal', 'area', 'tipoSala', 'turnoCatalogo']);

        return [
            'id' => $sala->id,
            'sucursal_id' => $sala->sucursal_id,
            'sucursal' => $sala->sucursal?->nombre,
            'area_id' => $sala->area_id,
            'area' => $sala->area?->nombre,
            'tipo_sala_id' => $sala->tipo_sala_id,
            'tipo_sala' => $sala->tipoSala?->nombre,
            'turno_catalogo_id' => $sala->turno_catalogo_id,
            'turno' => $sala->turnoCatalogo?->nombre,
            'nombre' => $sala->nombre,
            'observaciones' => $sala->observaciones,
            'activo' => $sala->activo,
            'version' => $sala->version,
            'updated_at' => $sala->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromAmbiente(Ambiente $ambiente): array
    {
        $ambiente->loadMissing('sucursal');

        return [
            'id' => $ambiente->id,
            'sucursal_id' => $ambiente->sucursal_id,
            'sucursal' => $ambiente->sucursal?->nombre,
            'nombre' => $ambiente->nombre,
            'uso' => $ambiente->uso->value,
            'uso_label' => $ambiente->uso->label(),
            'observaciones' => $ambiente->observaciones,
            'activo' => $ambiente->activo,
            'version' => $ambiente->version,
            'updated_at' => $ambiente->updated_at?->toIso8601String(),
        ];
    }
}
