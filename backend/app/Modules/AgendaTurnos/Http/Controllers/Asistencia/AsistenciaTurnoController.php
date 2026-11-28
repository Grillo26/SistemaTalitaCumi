<?php

namespace App\Modules\AgendaTurnos\Http\Controllers\Asistencia;

use App\Models\User;
use App\Modules\AgendaTurnos\Actions\Asistencia\CorregirAsistencia;
use App\Modules\AgendaTurnos\Actions\Asistencia\RegistrarAsistencia;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Http\Requests\Asistencia\CorregirAsistenciaRequest;
use App\Modules\AgendaTurnos\Http\Requests\Asistencia\RegistrarAsistenciaRequest;
use App\Modules\AgendaTurnos\Http\Resources\Asistencia\AsistenciaTurnoResource;
use App\Modules\AgendaTurnos\Queries\Asistencia\GetAsistenciaTurno;
use App\Modules\AgendaTurnos\Support\AsistenciaTurnosPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 07.04/RF-001 a RF-008, RF-011, RF-013.
 *
 * Controller delgado: valida formato, delega en Action/Query y devuelve un
 * Resource. Sin transacciones ni bitácora manual.
 */
final class AsistenciaTurnoController
{
    public function show(
        int $id,
        Request $request,
        GetAsistenciaTurno $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => (new AsistenciaTurnoResource($query($id, $actor)))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function registrar(
        int $id,
        RegistrarAsistenciaRequest $request,
        RegistrarAsistencia $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $action(
                $id,
                EstadoTurno::from((string) $data['estado']),
                $actor,
                isset($data['version']) ? (int) $data['version'] : null,
                $request->ip(),
            ),
            'can' => $this->can($actor),
        ]);
    }

    public function corregir(
        int $id,
        CorregirAsistenciaRequest $request,
        CorregirAsistencia $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $action(
                $id,
                EstadoTurno::from((string) $data['estado']),
                $actor,
                (int) $data['version'],
                isset($data['motivo']) ? (string) $data['motivo'] : null,
                $request->ip(),
            ),
            'can' => $this->can($actor),
        ]);
    }

    /**
     * @return array<string, bool>
     */
    private function can(User $actor): array
    {
        return [
            'ver' => $actor->can(AsistenciaTurnosPermission::VER),
            'registrar' => $actor->can(AsistenciaTurnosPermission::REGISTRAR),
            'corregir_fuera_ventana' => $actor->can(AsistenciaTurnosPermission::CORREGIR_FUERA_VENTANA),
            'ver_indicadores' => $actor->can(AsistenciaTurnosPermission::VER_INDICADORES),
            'resumen_diario' => $actor->can(AsistenciaTurnosPermission::RESUMEN_DIARIO),
            'exportar' => $actor->can(AsistenciaTurnosPermission::EXPORTAR),
        ];
    }
}
