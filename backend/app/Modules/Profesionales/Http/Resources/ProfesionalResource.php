<?php

namespace App\Modules\Profesionales\Http\Resources;

use App\Models\User;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Support\ProfesionalAbilities;
use App\Modules\Profesionales\Support\ProfesionalAssignability;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación del profesional (03/RF-D05).
 *
 * Incluye especialidades activas, sucursales visibles y asignabilidad. Nunca
 * expone datos de usuarios ni información de sucursales fuera del alcance.
 */
final class ProfesionalResource extends JsonResource
{
    /**
     * @param  list<int>|null  $accessibleBranchIds  NULL = alcance global
     * @return array<string, mixed>
     */
    public static function forActor(
        User $actor,
        Profesional $profesional,
        Request $request,
        ?array $accessibleBranchIds,
    ): array {
        $data = (new self($profesional))->resolve($request);

        if ($accessibleBranchIds !== null) {
            $data['sucursales'] = array_values(array_filter(
                $data['sucursales'],
                static fn (array $sucursal): bool => in_array((int) $sucursal['id'], $accessibleBranchIds, true),
            ));
        }

        $assignability = app(ProfesionalAssignability::class)->for($profesional, $accessibleBranchIds);

        $data['asignable'] = $assignability['asignable'];
        $data['motivos_no_asignable'] = $assignability['motivos'];
        $data['can'] = ProfesionalAbilities::for($actor, $profesional);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Profesional $profesional */
        $profesional = $this->resource;

        $profesional->loadMissing([
            'relacionesEspecialidad.especialidad',
            'relacionesSucursal.sucursal',
        ]);

        $especialidades = $profesional->relacionesEspecialidad
            ->filter(static fn ($relacion): bool => (bool) $relacion->activo && $relacion->especialidad !== null)
            ->sortBy(static fn ($relacion): string => sprintf(
                '%d-%s',
                $relacion->tipo->value === 'principal' ? 0 : 1,
                mb_strtolower((string) $relacion->especialidad->nombre),
            ))
            ->map(static fn ($relacion): array => [
                'relacion_id' => $relacion->id,
                'id' => $relacion->especialidad->id,
                'nombre' => $relacion->especialidad->nombre,
                'tipo' => $relacion->tipo->value,
                'activo' => (bool) $relacion->activo,
            ])
            ->values()
            ->all();

        $principal = collect($especialidades)->firstWhere('tipo', 'principal');

        $sucursales = $profesional->relacionesSucursal
            ->filter(static fn ($relacion): bool => $relacion->sucursal !== null)
            ->sortBy(static fn ($relacion): string => mb_strtolower((string) $relacion->sucursal->nombre))
            ->map(static fn ($relacion): array => [
                'id' => $relacion->sucursal->id,
                'codigo' => $relacion->sucursal->codigo,
                'nombre' => $relacion->sucursal->nombre,
                'activo' => (bool) $relacion->sucursal->activo,
                'relacion_id' => $relacion->id,
                'activo_relacion' => (bool) $relacion->activo,
                'motivo' => $relacion->motivo,
                'version' => $relacion->version,
            ])
            ->values()
            ->all();

        return [
            'id' => $profesional->id,
            'nombre_completo' => $profesional->nombre_completo,
            'ci' => $profesional->ci,
            'telefono' => $profesional->telefono,
            'telefono_2' => $profesional->telefono_2,
            'email' => $profesional->email,
            'matricula' => $profesional->matricula,
            'activo' => (bool) $profesional->activo,
            'estado' => $profesional->estado,
            'motivo_estado' => $profesional->motivo_estado,
            'estado_cambiado_en' => $profesional->estado_cambiado_en?->toIso8601String(),
            'especialidad_principal_id' => $principal['id'] ?? null,
            'especialidades' => $especialidades,
            'sucursales' => $sucursales,
            'version' => $profesional->version,
            'created_at' => $profesional->created_at?->toIso8601String(),
            'updated_at' => $profesional->updated_at?->toIso8601String(),
        ];
    }
}
