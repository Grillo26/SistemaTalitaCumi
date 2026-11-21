<?php

namespace App\Modules\Seguridad\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Respuesta de login: token y datos del usuario (RF-A01).
 */
final class SessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource['token'],
            'token_type' => 'Bearer',
            'expires_at' => $this->resource['expires_at'],
            'user' => (new AuthenticatedUserResource($this->resource['user']))->resolve($request),
        ];
    }
}
