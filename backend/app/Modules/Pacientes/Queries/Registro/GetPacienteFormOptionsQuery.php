<?php

namespace App\Modules\Pacientes\Queries\Registro;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Enums\Sexo;
use App\Modules\Pacientes\Support\PacienteAbilities;
use App\Modules\Pacientes\Support\PacientesModule;

/**
 * Opciones para formularios y filtros de pacientes.
 *
 * No devuelve datos falsos: el catálogo de diagnósticos y el filtro de
 * inscripción se informan como no disponibles.
 */
final class GetPacienteFormOptionsQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $actor): array
    {
        $branchIds = $this->access->accesibles($actor->id);

        $sucursales = $branchIds === []
            ? []
            : Branch::query()
                ->whereIn('id', $branchIds)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre'])
                ->map(static fn (Branch $branch): array => [
                    'id' => (int) $branch->id,
                    'codigo' => $branch->codigo,
                    'nombre' => $branch->nombre,
                ])
                ->values()
                ->all();

        return [
            'estados' => array_map(
                static fn (EstadoPaciente $estado): array => [
                    'value' => $estado->value,
                    'label' => $estado->label(),
                ],
                EstadoPaciente::cases(),
            ),
            'sexos' => array_map(
                static fn (Sexo $sexo): array => [
                    'value' => $sexo->value,
                    'label' => $sexo->label(),
                ],
                Sexo::cases(),
            ),
            'sucursales' => $sucursales,
            'tipos_diagnostico' => [
                'disponible' => PacientesModule::DIAGNOSTICOS_DISPONIBLES,
                'items' => [],
                'mensaje' => 'Disponible con el catálogo de diagnósticos (módulo 05).',
            ],
            'inscripciones' => [
                'disponible' => PacientesModule::INSCRIPCIONES_DISPONIBLES,
                'mensaje' => 'El filtro por sucursal de inscripción estará disponible con Inscripciones (módulo 06).',
            ],
            'tutores' => [
                'disponible' => PacientesModule::TUTORES_DISPONIBLES,
                'mensaje' => PacientesModule::TUTORES_DISPONIBLES
                    ? 'Tutores disponibles.'
                    : 'Disponible con el submódulo de tutores y contactos (04.02).',
            ],
            'puede_ver_sensibles' => $actor->can(\App\Modules\Pacientes\Support\PacientesPermission::PATIENT_VIEW_SENSITIVE),
            'can' => PacienteAbilities::collection($actor),
        ];
    }
}
