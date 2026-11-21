<?php

namespace App\Modules\Pacientes\Http\Controllers\Consentimiento;

use App\Models\User;
use App\Modules\Pacientes\Actions\Consentimiento\RegisterConsentimientoAction;
use App\Modules\Pacientes\Actions\Consentimiento\RejectConsentimientoDeletionAction;
use App\Modules\Pacientes\Actions\Consentimiento\RevokeConsentimientoAction;
use App\Modules\Pacientes\Exceptions\FichaIngresoNotFoundException;
use App\Modules\Pacientes\Http\Requests\Consentimiento\RevokeConsentimientoRequest;
use App\Modules\Pacientes\Http\Requests\Consentimiento\StoreConsentimientoRequest;
use App\Modules\Pacientes\Http\Resources\ConsentimientoResource;
use App\Modules\Pacientes\Http\Resources\ConsentimientoTipoResource;
use App\Modules\Pacientes\Models\Consentimiento;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Queries\Consentimiento\GetConsentimientoQuery;
use App\Modules\Pacientes\Queries\Consentimiento\ListConsentimientosQuery;
use App\Modules\Pacientes\Queries\Consentimiento\ListConsentimientosVigentesQuery;
use App\Modules\Pacientes\Queries\Consentimiento\ListConsentimientoTiposQuery;
use App\Modules\Pacientes\Queries\Ficha\GetFichaIngresoQuery;
use App\Modules\Pacientes\Queries\Registro\GetPacienteQuery;
use App\Modules\Pacientes\Support\FichaIngresoAbilities;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consentimientos de la ficha de ingreso (04.03 Fase B).
 *
 * No implementa la entrevista de 06 ni el registro clínico de 11.
 */
final class ConsentimientoController
{
    public function index(
        int $id,
        int $fichaId,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        ListConsentimientosQuery $getHistorial,
        ListConsentimientosVigentesQuery $getVigentes,
        ListConsentimientoTiposQuery $getTipos,
        AuditRecorderContract $audit,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $ficha->loadMissing(['paciente.relacionesTutor.tutor', 'paciente.relacionesTutor.parentesco']);

        $vigentes = $getVigentes($ficha);
        $vigenteIds = $vigentes->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $historial = $getHistorial($ficha);
        $parentescos = $ficha->paciente->relacionesTutor->keyBy('tutor_id');

        $this->audit($audit, $actor, $ficha, $request);

        return response()->json([
            'data' => [
                'vigentes' => $vigentes
                    ->map(static fn (Consentimiento $consentimiento): array => ConsentimientoResource::for(
                        $actor,
                        $consentimiento,
                        true,
                        $parentescos->get($consentimiento->tutor_id)?->parentesco,
                    ))
                    ->values()
                    ->all(),
                'historial' => $historial
                    ->map(static fn (Consentimiento $consentimiento): array => ConsentimientoResource::for(
                        $actor,
                        $consentimiento,
                        in_array((int) $consentimiento->id, $vigenteIds, true),
                        $parentescos->get($consentimiento->tutor_id)?->parentesco,
                    ))
                    ->values()
                    ->all(),
            ],
            'tipos' => $getTipos()
                ->map(static fn ($tipo): ?array => ConsentimientoTipoResource::for($tipo))
                ->values()
                ->all(),
            'tutores' => $this->tutores($ficha),
            'can' => FichaIngresoAbilities::for($actor, $ficha),
        ]);
    }

    public function store(
        int $id,
        int $fichaId,
        StoreConsentimientoRequest $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        RegisterConsentimientoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $consentimiento = $action($ficha, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => ConsentimientoResource::for(
                $actor,
                $consentimiento,
                $consentimiento->estaVigente(),
                $this->parentescoFor($ficha, (int) $consentimiento->tutor_id),
            ),
            'can' => FichaIngresoAbilities::for($actor, $ficha),
        ], 201);
    }

    public function revoke(
        int $id,
        int $fichaId,
        int $consentimientoId,
        RevokeConsentimientoRequest $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        GetConsentimientoQuery $getConsentimiento,
        RevokeConsentimientoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $consentimiento = $getConsentimiento($ficha, $consentimientoId);
        $revocacion = $action(
            $ficha,
            $consentimiento,
            (string) $request->validated()['motivo'],
            $actor,
            $request->ip(),
        );

        return response()->json([
            'data' => ConsentimientoResource::for(
                $actor,
                $revocacion,
                false,
                $this->parentescoFor($ficha, (int) $revocacion->tutor_id),
            ),
            'can' => FichaIngresoAbilities::for($actor, $ficha),
        ]);
    }

    public function destroy(
        int $id,
        int $fichaId,
        int $consentimientoId,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        GetConsentimientoQuery $getConsentimiento,
        RejectConsentimientoDeletionAction $action,
    ): never {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $getConsentimiento($ficha, $consentimientoId);
        $action();
    }

    private function findFicha(
        GetFichaIngresoQuery $getFicha,
        Paciente $paciente,
        User $actor,
        int $fichaId,
    ): FichaIngreso {
        $ficha = $getFicha($paciente, $actor, $fichaId);

        if ($ficha === null) {
            throw new FichaIngresoNotFoundException($fichaId);
        }

        return $ficha;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tutores(FichaIngreso $ficha): array
    {
        return $ficha->paciente->relacionesTutor
            ->filter(static fn ($relacion): bool => $relacion->tutor !== null && $relacion->tutor->activo)
            ->sortByDesc('es_principal')
            ->map(static fn ($relacion): array => [
                'id' => (int) $relacion->tutor_id,
                'nombre_completo' => $relacion->tutor->nombre_completo,
                'parentesco_id' => $relacion->parentesco_id === null ? null : (int) $relacion->parentesco_id,
                'parentesco' => $relacion->parentesco?->nombre ?? $relacion->tutor->parentesco,
                'es_principal' => (bool) $relacion->es_principal,
            ])
            ->values()
            ->all();
    }

    private function parentescoFor(FichaIngreso $ficha, int $tutorId): ?Parentesco
    {
        $ficha->loadMissing('paciente.relacionesTutor.parentesco');

        return $ficha->paciente->relacionesTutor
            ->firstWhere('tutor_id', $tutorId)
            ?->parentesco;
    }

    private function audit(
        AuditRecorderContract $audit,
        User $actor,
        FichaIngreso $ficha,
        Request $request,
    ): void {
        $audit->registrarConsultaSensible(
            entidad: 'ficha_ingreso',
            entidadId: (int) $ficha->id,
            sucursalId: (int) $ficha->sucursal_ingreso_id,
            contexto: ['recurso' => 'ficha_ingreso_consentimientos'],
            usuarioId: $actor->id,
            ip: $request->ip(),
        );
    }
}
