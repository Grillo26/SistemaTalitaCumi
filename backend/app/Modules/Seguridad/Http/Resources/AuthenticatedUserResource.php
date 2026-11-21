<?php

namespace App\Modules\Seguridad\Http\Resources;

use App\Models\User;
use App\Modules\Seguridad\Queries\Autenticacion\GetAuthenticatedUserQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuario autenticado con permisos y sucursales (RF-A16, RF-R06).
 */
final class AuthenticatedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return app(GetAuthenticatedUserQuery::class)($user);
    }
}
