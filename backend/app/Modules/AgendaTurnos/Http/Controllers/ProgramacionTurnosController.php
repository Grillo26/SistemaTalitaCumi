<?php

namespace App\Modules\AgendaTurnos\Http\Controllers;

use App\Models\User;
use App\Modules\AgendaTurnos\Actions\AsignarProfesionalATurnos;
use App\Modules\AgendaTurnos\Actions\GenerarTurnosDesdeInscripcionSala;
use App\Modules\AgendaTurnos\Actions\GenerarTurnosDesdePaquete;
use App\Modules\AgendaTurnos\Actions\RegenerarTurnos;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Http\Requests\AsignarProfesionalRequest;
use App\Modules\AgendaTurnos\Http\Requests\GenerarTurnosRequest;
use App\Modules\AgendaTurnos\Http\Requests\PreviewGeneracionRequest;
use App\Modules\AgendaTurnos\Http\Requests\PreviewRegeneracionRequest;
use App\Modules\AgendaTurnos\Http\Requests\RegenerarTurnosRequest;
use App\Modules\AgendaTurnos\Http\Resources\LoteGeneracionResource;
use App\Modules\AgendaTurnos\Http\Resources\OrigenProgramacionResource;
use App\Modules\AgendaTurnos\Http\Resources\PreviewGeneracionResource;
use App\Modules\AgendaTurnos\Http\Resources\PreviewRegeneracionResource;
use App\Modules\AgendaTurnos\Http\Resources\TurnoOrigenResource;
use App\Modules\AgendaTurnos\Queries\GetOrigenProgramacion;
use App\Modules\AgendaTurnos\Queries\GetTurnosDeOrigen;
use App\Modules\AgendaTurnos\Queries\PreviewGeneracionTurnos;
use App\Modules\AgendaTurnos\Queries\PreviewRegeneracion;
use App\Modules\AgendaTurnos\Support\AgendaTurnosPermission;
use App\Modules\AgendaTurnos\Support\EntradaGeneracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Programación de Turnos] RF-001 a RF-016.
 * RN-001 a RN-010. CA-001 a CA-010.
 *
 * Controller delgado: valida formato, delega en Action o Query y devuelve
 * Resources. Sin transacciones, sin auditoría manual, sin lógica de negocio.
 */
final class ProgramacionTurnosController
{
    /**
     * @return array<string, bool>
     */
    private function can(User $actor): array
    {
        return [
            'ver' => $actor->can(AgendaTurnosPermission::VER),
            'generar' => $actor->can(AgendaTurnosPermission::GENERAR),
            'regenerar' => $actor->can(AgendaTurnosPermission::REGENERAR),
            'asignar_profesional' => $actor->can(AgendaTurnosPermission::ASIGNAR_PROFESIONAL),
            'feriados' => $actor->can(AgendaTurnosPermission::FERIADOS),
        ];
    }

    private function tipo(Request $request): TipoOrigenTurno
    {
        return TipoOrigenTurno::from((string) $request->route('tipo'));
    }

    public function origen(
        Request $request,
        GetOrigenProgramacion $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $query(
            $this->tipo($request),
            (int) $request->route('id'),
            $actor,
        );

        return response()->json([
            'data' => (new OrigenProgramacionResource($data))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function turnos(
        Request $request,
        GetTurnosDeOrigen $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $query(
            $this->tipo($request),
            (int) $request->route('id'),
            $actor,
        );

        return response()->json([
            'data' => [
                'origen' => (new OrigenProgramacionResource($data['origen']))->resolve($request),
                'turnos' => TurnoOrigenResource::collection($data['turnos'])->resolve($request),
                'total' => $data['total'],
            ],
            'can' => $this->can($actor),
        ]);
    }

    public function previewGeneracion(
        PreviewGeneracionRequest $request,
        PreviewGeneracionTurnos $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        [$usarOrigen, $profesionalId] = $this->profesionalEfectivo($data);

        $resultado = $query(
            $this->tipo($request),
            (int) $request->route('id'),
            $actor,
            $usarOrigen,
            $profesionalId,
        );

        return response()->json([
            'data' => (new PreviewGeneracionResource($resultado))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function generar(
        GenerarTurnosRequest $request,
        GenerarTurnosDesdePaquete $desdePaquete,
        GenerarTurnosDesdeInscripcionSala $desdeSala,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $entrada = $this->entrada($request);
        $tipo = $this->tipo($request);
        $origenId = (int) $request->route('id');

        $resultado = $tipo === TipoOrigenTurno::PAQUETE_SESIONES
            ? $desdePaquete($origenId, $entrada, $actor)
            : $desdeSala($origenId, $entrada, $actor);

        return response()->json([
            'data' => (new LoteGeneracionResource($resultado))->resolve($request),
            'can' => $this->can($actor),
        ], 201);
    }

    public function previewRegeneracion(
        PreviewRegeneracionRequest $request,
        PreviewRegeneracion $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $resultado = $query(
            $this->tipo($request),
            (int) $request->route('id'),
            $actor,
        );

        return response()->json([
            'data' => (new PreviewRegeneracionResource($resultado))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function regenerar(
        RegenerarTurnosRequest $request,
        RegenerarTurnos $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $resultado = $action(
            $this->tipo($request),
            (int) $request->route('id'),
            $this->entrada($request),
            $actor,
        );

        return response()->json([
            'data' => (new LoteGeneracionResource($resultado))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function asignarProfesional(
        AsignarProfesionalRequest $request,
        AsignarProfesionalATurnos $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $resultado = $action(
            (int) $data['profesional_id'],
            isset($data['lote_generacion_id']) ? (int) $data['lote_generacion_id'] : null,
            isset($data['turno_ids']) && is_array($data['turno_ids'])
                ? array_values(array_map(static fn (mixed $id): int => (int) $id, $data['turno_ids']))
                : null,
            $actor,
            $request->ip(),
        );

        return response()->json([
            'data' => $resultado,
            'can' => $this->can($actor),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function entrada(Request $request): EntradaGeneracion
    {
        $data = $request->validated();
        [$usarOrigen, $profesionalId] = $this->profesionalEfectivo($data);

        return new EntradaGeneracion(
            claveIdempotencia: (string) $data['clave_idempotencia'],
            confirmarAdvertencias: (bool) ($data['confirmar_advertencias'] ?? false),
            usarProfesionalOrigen: $usarOrigen,
            profesionalId: $profesionalId,
            ip: $request->ip(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: bool, 1: ?int}
     */
    private function profesionalEfectivo(array $data): array
    {
        $usarOrigen = array_key_exists('usar_profesional_origen', $data)
            ? (bool) $data['usar_profesional_origen']
            : ! array_key_exists('profesional_id', $data);

        $profesionalId = isset($data['profesional_id']) ? (int) $data['profesional_id'] : null;

        return [$usarOrigen, $profesionalId];
    }
}
