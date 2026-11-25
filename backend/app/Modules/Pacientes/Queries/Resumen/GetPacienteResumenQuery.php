<?php

namespace App\Modules\Pacientes\Queries\Resumen;

use App\Models\User;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Pacientes\Exceptions\PacienteResumenSucursalInactivaException;
use App\Modules\Pacientes\Exceptions\PacienteResumenSucursalInvalidaException;
use App\Modules\Pacientes\Http\Resources\ContactoEmergenciaResource;
use App\Modules\Pacientes\Http\Resources\PacienteResource;
use App\Modules\Pacientes\Http\Resources\TutorResource;
use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteResumenBlock;
use App\Modules\Pacientes\Support\PacienteResumenBlocksBuilder;
use App\Modules\Pacientes\Support\PacienteResumenMotivo;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Http\Request;

/**
 * Ficha resumen del paciente (04.04 Fase B).
 *
 * Es una lectura agregada de solo lectura. No llama a la ficha completa ni
 * a Actions; los bloques de 06/07/08/09 se resuelven por contratos y se
 * degradan a `no_disponible` si el módulo no existe o falla.
 */
final class GetPacienteResumenQuery
{
    public function __construct(
        private readonly PacienteAccessScope $scope,
        private readonly BranchDirectoryContract $branches,
        private readonly PacienteResumenBlocksBuilder $indicators,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        Paciente $paciente,
        User $actor,
        Request $request,
        ?int $sucursalId = null,
    ): array {
        $sucursalesAccesibles = $this->scope->accessibleBranchIds($actor);
        $this->assertSucursalContexto($sucursalId, $sucursalesAccesibles);

        $paciente->loadMissing([
            'sucursalRegistro',
            'relacionesTutor.tutor.parentescoCatalogo',
            'relacionesTutor.parentesco',
            'contactosEmergencia.parentesco',
        ]);

        $indicadores = $this->indicators->indicators(
            $paciente,
            $actor,
            $sucursalesAccesibles,
            $sucursalId,
        );

        $pacienteData = PacienteResource::forList($actor, $paciente, $request);

        $bloques = [
            'datos_basicos' => PacienteResumenBlock::disponible(
                'Datos del paciente.',
                $pacienteData,
            )->toArray(),
            'datos_sensibles' => PacienteResumenBlock::disponible(
                'Solo se muestran banderas de presencia; el contenido se consulta en la ficha completa.',
                [
                    'tiene_alergias' => filled($paciente->alergias),
                    'tiene_condiciones_medicas' => filled($paciente->condiciones_medicas),
                    'tiene_observaciones' => filled($paciente->observaciones),
                ],
            )->toArray(),
            'tutores' => $this->tutores($paciente, $actor, $request)->toArray(),
            'contactos_emergencia' => $this->contactos($paciente, $actor, $request)->toArray(),
        ];

        $bloques += $indicadores['bloques'];

        return [
            'paciente' => $pacienteData,
            'bloques' => $bloques,
            'contexto_sucursal' => $sucursalId,
            'sucursales_accesibles' => $sucursalesAccesibles,
            'fecha_corte' => $indicadores['fecha_corte'],
            'generado_en' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     */
    private function assertSucursalContexto(?int $sucursalId, ?array $sucursalesAccesibles): void
    {
        if ($sucursalId === null) {
            return;
        }

        if ($sucursalesAccesibles !== null && ! in_array($sucursalId, $sucursalesAccesibles, true)) {
            throw new PacienteResumenSucursalInvalidaException;
        }

        if (! $this->branches->estaActiva($sucursalId)) {
            throw new PacienteResumenSucursalInactivaException;
        }
    }

    private function tutores(Paciente $paciente, User $actor, Request $request): PacienteResumenBlock
    {
        if (! $actor->can(PacientesPermission::TUTOR_VIEW)) {
            return PacienteResumenBlock::noDisponible(
                PacienteResumenMotivo::SIN_PERMISO,
                'No tenés permiso para ver los tutores del paciente.',
            );
        }

        $items = $paciente->relacionesTutor
            ->filter(static fn (PacienteTutor $relacion): bool => $relacion->tutor !== null)
            ->sortBy(static fn (PacienteTutor $relacion): string => sprintf(
                '%d-%s',
                $relacion->es_principal ? 0 : 1,
                mb_strtolower((string) $relacion->tutor->nombre_completo),
            ))
            ->map(static fn (PacienteTutor $relacion): array => TutorResource::forPaciente(
                $actor,
                $relacion,
                $request,
            ))
            ->filter(static fn (array $tutor): bool => $tutor !== [])
            ->values()
            ->all();

        return PacienteResumenBlock::disponible(
            $items === [] ? 'Sin tutores asociados.' : 'Tutores del paciente.',
            ['items' => $items],
        );
    }

    private function contactos(Paciente $paciente, User $actor, Request $request): PacienteResumenBlock
    {
        if (! $actor->can(PacientesPermission::CONTACT_EMERGENCY_VIEW)) {
            return PacienteResumenBlock::noDisponible(
                PacienteResumenMotivo::SIN_PERMISO,
                'No tenés permiso para ver los contactos de emergencia.',
            );
        }

        $items = $paciente->contactosEmergencia
            ->sortBy(static fn (ContactoEmergencia $contacto): string => mb_strtolower((string) $contacto->nombre_completo))
            ->map(static fn (ContactoEmergencia $contacto): array => ContactoEmergenciaResource::for(
                $actor,
                $contacto,
                $request,
            ))
            ->values()
            ->all();

        return PacienteResumenBlock::disponible(
            $items === [] ? 'Sin contactos de emergencia registrados.' : 'Contactos de emergencia.',
            ['items' => $items],
        );
    }
}
