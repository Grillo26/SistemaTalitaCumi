<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController
{
    /**
     * Estado de la API y de su conexión con PostgreSQL.
     */
    public function __invoke(): JsonResponse
    {
        $database = [
            'status' => 'disconnected',
            'driver' => config('database.default'),
        ];

        $httpStatus = 503;

        try {
            DB::select('select 1');

            $database = [
                'status' => 'connected',
                'driver' => config('database.default'),
                'database' => DB::connection()->getDatabaseName(),
            ];

            $httpStatus = 200;
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'status' => $httpStatus === 200 ? 'ok' : 'degraded',
            'service' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
            'app' => [
                'environment' => config('app.env'),
                'locale' => config('app.locale'),
                'timezone' => config('app.timezone'),
                'currency' => config('app.currency'),
                'currency_symbol' => config('app.currency_symbol'),
            ],
            'database' => $database,
        ], $httpStatus);
    }
}
