<?php

use App\Modules\Seguridad\Http\Middleware\EnsureHttps;
use App\Modules\Seguridad\Http\Middleware\EnsureSessionIsValid;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Las rutas API no deben intentar redirigir al login web cuando falta
        // el token: deben responder 401 JSON.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : '/login',
        );

        // 02 / RF-A07, RF-A10, RF-A13: vigila la sesión autenticada.
        // Se ejecuta después de auth:sanctum para poder leer el usuario.
        $middleware->api(append: [EnsureSessionIsValid::class, EnsureHttps::class]);
        $middleware->appendToPriorityList(Authenticate::class, EnsureSessionIsValid::class);

        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Sesión no iniciada o token inválido.',
                    'code' => 'UNAUTHENTICATED',
                ], 401);
            }
        });

        $exceptions->render(function (UnauthorizedException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'No tenés permisos para realizar esta acción.',
                    'code' => 'FORBIDDEN',
                ], 403);
            }
        });
    })->create();
