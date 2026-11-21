<?php

namespace App\Modules\Seguridad\Queries\Auditoria;

use App\Models\User;
use App\Modules\Seguridad\Models\AccionAuditada;
use App\Modules\Seguridad\Models\BitacoraEntry;
use App\Modules\Seguridad\Support\AuditModuleResolver;
use App\Modules\Seguridad\Support\AuditScope;
use App\Modules\Seguridad\Support\SecurityParameters;

/**
 * Opciones de filtros de la bitácora (02/RF-B04, RF-B03).
 */
final class GetAuditFilterOptionsQuery
{
    public function __construct(
        private readonly AuditScope $scope,
        private readonly SecurityParameters $parameters,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $actor): array
    {
        $base = BitacoraEntry::query();
        $this->scope->apply($base, $actor);

        $users = (clone $base)
            ->whereNotNull('usuario_id')
            ->join('users', 'users.id', '=', 'bitacora.usuario_id')
            ->select('users.id', 'users.name', 'users.email')
            ->distinct()
            ->orderBy('users.name')
            ->get()
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'nombre' => (string) $row->name,
                'email' => (string) $row->email,
            ])
            ->values()
            ->all();

        $catalog = AccionAuditada::query()
            ->where('activo', true)
            ->orderBy('modulo')
            ->orderBy('entidad')
            ->orderBy('accion')
            ->get();

        $modules = $catalog->pluck('modulo')
            ->merge(['organizacion', 'usuarios', 'autenticacion', 'roles', 'auditoria'])
            ->unique()
            ->sort()
            ->values()
            ->map(static fn (string $module): array => [
                'clave' => $module,
                'label' => AuditModuleResolver::moduleLabel($module),
            ])
            ->all();

        $entities = $catalog->pluck('entidad')
            ->merge((clone $base)->whereNotNull('entidad')->distinct()->pluck('entidad'))
            ->unique()
            ->sort()
            ->values()
            ->map(static fn (string $entity): array => [
                'clave' => $entity,
                'label' => AuditModuleResolver::entityLabel($entity),
            ])
            ->all();

        $actions = $catalog
            ->map(static fn (AccionAuditada $action): array => [
                'clave' => $action->accion,
                'label' => $action->descripcion ?? AuditModuleResolver::actionLabel($action->accion),
                'modulo' => $action->modulo,
                'entidad' => $action->entidad,
            ])
            ->merge(
                (clone $base)
                    ->whereNotNull('accion')
                    ->distinct()
                    ->pluck('accion')
                    ->map(static fn (string $action): array => [
                        'clave' => $action,
                        'label' => AuditModuleResolver::actionLabel($action),
                        'modulo' => null,
                        'entidad' => null,
                    ]),
            )
            ->unique('clave')
            ->sortBy('label')
            ->values()
            ->all();

        return [
            'usuarios' => $users,
            'modulos' => $modules,
            'entidades' => $entities,
            'acciones' => $actions,
            'resultados' => [
                ['clave' => 'exito', 'label' => 'Éxito'],
                ['clave' => 'fallo', 'label' => 'Fallo'],
                ['clave' => 'denegado', 'label' => 'Denegado'],
            ],
            'retencion_anios' => $this->parameters->auditRetentionYears(),
        ];
    }
}
