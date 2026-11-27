<?php

namespace App\Modules\AgendaTurnos\Http\Controllers\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Actions\Cambios\CancelarTurno;
use App\Modules\AgendaTurnos\Actions\Cambios\CancelarTurnosMasivo;
use App\Modules\AgendaTurnos\Actions\Cambios\ReprogramarTurno;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Http\Requests\Cambios\CancelacionMasivaRequest;
use App\Modules\AgendaTurnos\Http\Requests\Cambios\CancelacionRequest;
use App\Modules\AgendaTurnos\Http\Requests\Cambios\ContactoWhatsappRequest;
use App\Modules\AgendaTurnos\Http\Requests\Cambios\EjecutarCancelacionMasivaRequest;
use App\Modules\AgendaTurnos\Http\Requests\Cambios\ListMotivosRequest;
use App\Modules\AgendaTurnos\Http\Requests\Cambios\ReprogramacionRequest;
use App\Modules\AgendaTurnos\Http\Resources\Cambios\ContactoWhatsappResource;
use App\Modules\AgendaTurnos\Http\Resources\Cambios\HistorialTurnoResource;
use App\Modules\AgendaTurnos\Http\Resources\Cambios\PreviewCancelacionMasivaResource;
use App\Modules\AgendaTurnos\Http\Resources\Cambios\PreviewCancelacionResource;
use App\Modules\AgendaTurnos\Http\Resources\Cambios\PreviewReprogramacionResource;
use App\Modules\AgendaTurnos\Http\Resources\Cambios\TurnoCambioResource;
use App\Modules\AgendaTurnos\Queries\Cambios\GetHistorialTurno;
use App\Modules\AgendaTurnos\Queries\Cambios\GetOrigenesCancelables;
use App\Modules\AgendaTurnos\Queries\Cambios\GetTurnoCambio;
use App\Modules\AgendaTurnos\Queries\Cambios\GetTurnoContactoWhatsApp;
use App\Modules\AgendaTurnos\Queries\Cambios\ListMotivosTurno;
use App\Modules\AgendaTurnos\Queries\Cambios\PreviewCancelacion;
use App\Modules\AgendaTurnos\Queries\Cambios\PreviewCancelacionMasiva;
use App\Modules\AgendaTurnos\Queries\Cambios\PreviewReprogramacion;
use App\Modules\AgendaTurnos\Support\CambiosTurnosPermission;
use App\Modules\AgendaTurnos\Support\ReprogramacionInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Reprogramación y Cancelación] RF-001 a RF-011, RF-013 a RF-015.
 * RN-001 a RN-009. CA-001 a CA-009.
 *
 * Controller delgado: valida formato, delega en Action/Query y devuelve
 * Resources. Sin transacciones, sin bitácora manual.
 */
final class CambiosTurnoController
{
    /**
     * @return array<string, bool>
     */
    private function can(User $actor): array
    {
        return [
            'ver' => $actor->can('turno.ver'),
            'reprogramar' => $actor->can(CambiosTurnosPermission::REPROGRAMAR),
            'cancelar' => $actor->can(CambiosTurnosPermission::CANCELAR),
            'cancelar_masivo' => $actor->can(CambiosTurnosPermission::CANCELAR_MASIVO),
            'historial' => $actor->can(CambiosTurnosPermission::HISTORIAL),
            'reporte' => $actor->can(CambiosTurnosPermission::REPORTE),
            'exportar' => $actor->can(CambiosTurnosPermission::EXPORTAR),
        ];
    }

    public function show(
        int $id,
        Request $request,
        GetTurnoCambio $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => (new TurnoCambioResource($query($id, $actor)))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function historial(
        int $id,
        Request $request,
        GetHistorialTurno $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => (new HistorialTurnoResource($query($id, $actor)))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function whatsapp(
        int $id,
        ContactoWhatsappRequest $request,
        GetTurnoContactoWhatsApp $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => (new ContactoWhatsappResource(
                $query($id, $actor, (string) ($data['accion'] ?? 'reprogramado')),
            ))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function previewReprogramacion(
        int $id,
        ReprogramacionRequest $request,
        PreviewReprogramacion $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => (new PreviewReprogramacionResource(
                $query($id, $this->input($request), $actor),
            ))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function reprogramar(
        int $id,
        ReprogramacionRequest $request,
        ReprogramarTurno $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $action($id, $this->input($request), $actor, $request->ip()),
            'can' => $this->can($actor),
        ]);
    }

    public function previewCancelacion(
        int $id,
        CancelacionRequest $request,
        PreviewCancelacion $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => (new PreviewCancelacionResource($query(
                $id,
                (int) $data['motivo_id'],
                isset($data['motivo_texto']) ? (string) $data['motivo_texto'] : null,
                EstadoTurno::from((string) $data['estado_esperado']),
                $actor,
            )))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function cancelar(
        int $id,
        CancelacionRequest $request,
        CancelarTurno $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $action(
                $id,
                (int) $data['motivo_id'],
                isset($data['motivo_texto']) ? (string) $data['motivo_texto'] : null,
                EstadoTurno::from((string) $data['estado_esperado']),
                $actor,
                $request->ip(),
            ),
            'can' => $this->can($actor),
        ]);
    }

    public function motivos(
        ListMotivosRequest $request,
        ListMotivosTurno $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $query((string) $data['accion']),
            'can' => $this->can($actor),
        ]);
    }

    public function origenesCancelables(
        int $pacienteId,
        Request $request,
        GetOrigenesCancelables $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($pacienteId, $actor),
            'can' => $this->can($actor),
        ]);
    }

    public function previewMasiva(
        CancelacionMasivaRequest $request,
        PreviewCancelacionMasiva $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => (new PreviewCancelacionMasivaResource($query(
                (int) $data['paciente_id'],
                $this->entidades($data['entidades']),
                isset($data['servicio_id']) ? (int) $data['servicio_id'] : null,
                (int) $data['motivo_id'],
                isset($data['motivo_texto']) ? (string) $data['motivo_texto'] : null,
                $actor,
            )))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function cancelarMasivo(
        EjecutarCancelacionMasivaRequest $request,
        CancelarTurnosMasivo $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $action(
                (int) $data['paciente_id'],
                $this->entidades($data['entidades']),
                isset($data['servicio_id']) ? (int) $data['servicio_id'] : null,
                (int) $data['motivo_id'],
                isset($data['motivo_texto']) ? (string) $data['motivo_texto'] : null,
                array_values(array_map(static fn (mixed $id): int => (int) $id, $data['turno_ids'])),
                $actor,
                $request->ip(),
            ),
            'can' => $this->can($actor),
        ]);
    }

    private function input(ReprogramacionRequest $request): ReprogramacionInput
    {
        $data = $request->validated();

        return new ReprogramacionInput(
            fecha: (string) $data['fecha'],
            horaInicio: (string) $data['hora_inicio'],
            motivoId: (int) $data['motivo_id'],
            motivoTexto: isset($data['motivo_texto']) ? (string) $data['motivo_texto'] : null,
            estadoEsperado: EstadoTurno::from((string) $data['estado_esperado']),
            profesionalProporcionado: array_key_exists('profesional_id', $data),
            profesionalId: isset($data['profesional_id']) ? (int) $data['profesional_id'] : null,
            salaProporcionada: array_key_exists('sala_id', $data),
            salaId: isset($data['sala_id']) ? (int) $data['sala_id'] : null,
            confirmarAdvertencias: (bool) ($data['confirmar_advertencias'] ?? false),
        );
    }

    /**
     * @param  list<array{tipo: string, id: int}>  $entidades
     * @return list<array{tipo: string, id: int}>
     */
    private function entidades(array $entidades): array
    {
        return array_map(
            static fn (array $entidad): array => [
                'tipo' => (string) $entidad['tipo'],
                'id' => (int) $entidad['id'],
            ],
            $entidades,
        );
    }
}
