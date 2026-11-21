<?php

namespace App\Modules\Seguridad\Queries\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Models\BitacoraEntry;
use App\Modules\Seguridad\Support\UserAccessScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Historial de eventos del usuario para la ficha (02/RF-U12, 02/RF-U14).
 */
final class GetUserHistoryQuery
{
    public function __construct(private readonly UserAccessScope $scope) {}

    public function __invoke(
        User $target,
        User $actor,
        int $page,
        int $perPage,
    ): LengthAwarePaginator {
        $this->scope->assertCanAccess($actor, $target);

        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        return BitacoraEntry::query()
            ->with('usuario:id,name')
            ->where('entidad', 'usuario')
            ->where('entidad_id', $target->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
