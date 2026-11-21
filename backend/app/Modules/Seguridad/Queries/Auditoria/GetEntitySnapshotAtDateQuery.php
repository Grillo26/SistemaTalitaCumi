<?php

namespace App\Modules\Seguridad\Queries\Auditoria;

use App\Models\User;
use App\Modules\Seguridad\Enums\AuditResult;
use App\Modules\Seguridad\Models\BitacoraEntry;
use App\Modules\Seguridad\Support\AuditDateRange;
use App\Modules\Seguridad\Support\AuditScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reconstruye el estado de una entidad a una fecha (02/RF-B10, RF-B11).
 *
 * No hay versionado complejo: se toma el último evento exitoso con
 * snapshot completo de la entidad anterior al fin del día y se devuelven
 * sus snapshots. El scoping se aplica antes de buscar para no revelar la
 * existencia de eventos de otras sucursales.
 */
final class GetEntitySnapshotAtDateQuery
{
    /**
     * Acciones que guardan un snapshot completo de la entidad.
     *
     * @var list<string>
     */
    private const SNAPSHOT_ACTIONS = [
        'CREAR',
        'EDITAR',
        'ACTUALIZAR',
        'ACTIVAR',
        'DESACTIVAR',
        'RESETEAR_PASSWORD',
        'DESBLOQUEAR',
        'RESTAURAR',
        'EDITAR_PERMISOS',
    ];

    public function __construct(private readonly AuditScope $scope) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        string $entity,
        int $entityId,
        string $date,
        User $actor,
    ): array {
        $endOfDay = AuditDateRange::end($date);

        $query = BitacoraEntry::query()
            ->with([
                'usuario:id,name,email',
                'sucursal:id,codigo,nombre',
            ])
            ->where('entidad', $entity)
            ->where('entidad_id', $entityId)
            ->where('created_at', '<=', $endOfDay)
            ->whereIn('accion', self::SNAPSHOT_ACTIONS)
            ->where(static function (Builder $query): void {
                $query->where('resultado', AuditResult::EXITO->value)
                    ->orWhereNull('resultado');
            });

        $this->scope->apply($query, $actor);

        $entry = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($entry === null) {
            return [
                'entidad' => $entity,
                'entidad_id' => $entityId,
                'fecha' => $date,
                'encontrado' => false,
                'estado' => null,
                'anterior' => null,
                'accion' => null,
                'registrado_en' => null,
                'usuario' => null,
                'sucursal' => null,
            ];
        }

        return [
            'entidad' => $entity,
            'entidad_id' => $entityId,
            'fecha' => $date,
            'encontrado' => true,
            'estado' => $entry->datos_nuevos,
            'anterior' => $entry->datos_anteriores,
            'accion' => $entry->accion,
            'registrado_en' => $entry->created_at?->toIso8601String(),
            'usuario' => $entry->usuario?->name,
            'sucursal' => $entry->sucursal?->codigo,
        ];
    }
}
