<?php

namespace App\Modules\Seguridad\Http\Controllers;

use App\Models\User;
use App\Modules\Seguridad\Http\Resources\UserHistoryResource;
use App\Modules\Seguridad\Queries\Usuarios\GetUserHistoryQuery;
use App\Modules\Seguridad\Queries\Usuarios\GetUserQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Historial de eventos del usuario (02/RF-U12, 02/RF-U14).
 */
final class UserHistoryController
{
    public function index(
        int $id,
        Request $request,
        GetUserQuery $getUser,
        GetUserHistoryQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $target = $getUser($id, $actor);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));

        $paginator = $query($target, $actor, $page, $perPage);

        return response()->json([
            'data' => UserHistoryResource::collection($paginator->items())->resolve($request),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
