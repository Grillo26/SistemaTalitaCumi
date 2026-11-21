<?php

namespace App\Modules\Seguridad\Http\Middleware;

use App\Modules\Seguridad\Exceptions\HttpsRequiredException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige HTTPS cuando `seguridad.forzar_https` está activo (02/RF-A14).
 *
 * En desarrollo queda desactivado para no romper http://localhost:8080.
 * Detrás de TLS/proxy, configurar TRUSTED_PROXIES para que `isSecure()`
 * lea correctamente X-Forwarded-Proto.
 */
final class EnsureHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('seguridad.forzar_https', false) && ! $request->isSecure()) {
            throw new HttpsRequiredException;
        }

        return $next($request);
    }
}
