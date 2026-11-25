<?php

namespace App\Modules\Pacientes\Http\Controllers\Contactos;

use App\Models\User;
use App\Modules\Pacientes\Actions\Contactos\CreateContactoEmergenciaAction;
use App\Modules\Pacientes\Actions\Contactos\DeleteContactoEmergenciaAction;
use App\Modules\Pacientes\Actions\Contactos\UpdateContactoEmergenciaAction;
use App\Modules\Pacientes\Http\Requests\Contactos\StoreContactoEmergenciaRequest;
use App\Modules\Pacientes\Http\Requests\Contactos\UpdateContactoEmergenciaRequest;
use App\Modules\Pacientes\Http\Resources\ContactoEmergenciaResource;
use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Queries\Contactos\GetContactoEmergenciaQuery;
use App\Modules\Pacientes\Queries\Contactos\ListContactosEmergenciaQuery;
use App\Modules\Pacientes\Queries\Registro\GetPacienteQuery;
use App\Modules\Pacientes\Support\PacienteAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contactos de emergencia de un paciente (04.02/RF-012).
 */
final class PacienteContactoEmergenciaController
{
    public function index(
        int $id,
        Request $request,
        GetPacienteQuery $getPaciente,
        ListContactosEmergenciaQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);

        $items = $query($paciente)
            ->map(static fn (ContactoEmergencia $contacto): array => ContactoEmergenciaResource::for(
                $actor,
                $contacto,
                $request,
            ))
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
            'can' => PacienteAbilities::for($actor, $paciente),
        ]);
    }

    public function store(
        int $id,
        StoreContactoEmergenciaRequest $request,
        GetPacienteQuery $getPaciente,
        CreateContactoEmergenciaAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $contacto = $action($paciente, $request->validated(), $actor, $request->ip());
        $contacto->load('parentesco');

        return response()->json([
            'data' => ContactoEmergenciaResource::for($actor, $contacto, $request),
            'can' => PacienteAbilities::for($actor, $paciente),
        ], 201);
    }

    public function update(
        int $id,
        int $contactoId,
        UpdateContactoEmergenciaRequest $request,
        GetPacienteQuery $getPaciente,
        GetContactoEmergenciaQuery $getContacto,
        UpdateContactoEmergenciaAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $contacto = $getContacto($paciente, $contactoId);
        $data = $request->validated();
        $expectedVersion = (int) $data['version'];
        unset($data['version']);

        $contacto = $action($paciente, $contacto, $data, $expectedVersion, $actor, $request->ip());
        $contacto->load('parentesco');

        return response()->json([
            'data' => ContactoEmergenciaResource::for($actor, $contacto, $request),
            'can' => PacienteAbilities::for($actor, $paciente),
        ]);
    }

    public function destroy(
        int $id,
        int $contactoId,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetContactoEmergenciaQuery $getContacto,
        DeleteContactoEmergenciaAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $contacto = $getContacto($paciente, $contactoId);
        $action($paciente, $contacto, $actor, $request->ip());

        return response()->json([
            'message' => 'Contacto de emergencia eliminado.',
            'can' => PacienteAbilities::for($actor, $paciente),
        ]);
    }
}
