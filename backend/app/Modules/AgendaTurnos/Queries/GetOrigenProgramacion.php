<?php

namespace App\Modules\AgendaTurnos\Queries;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\AgendaTurnos\Support\OrigenProgramacionResolver;
use App\Modules\AgendaTurnos\Support\TurnosExistentesResolver;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;

/**
 * [Programación de Turnos] RF-002 / RF-003 / RF-013.
 *
 * Datos del origen y estado de la programación para abrir el asistente.
 */
final class GetOrigenProgramacion
{
    public function __construct(
        private readonly OrigenProgramacionResolver $origenes,
        private readonly TurnosExistentesResolver $turnos,
        private readonly DirectoriosAgenda $nombres,
        private readonly UserBranchAccessContract $branchAccess,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(TipoOrigenTurno $tipo, int $origenId, User $actor): array
    {
        $origen = $this->origenes->resolver($tipo, $origenId);

        if (! $this->branchAccess->puedeAcceder((int) $actor->id, $origen->sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        $activos = $this->turnos->activosDelOrigen($tipo, $origenId);
        $existentes = $this->turnos->delOrigen($tipo, $origenId);

        return [
            'origen' => array_merge($origen->toArray(), [
                'paciente' => [
                    'id' => $origen->pacienteId,
                    'nombre' => $this->nombres->paciente($origen->pacienteId),
                ],
                'servicio' => [
                    'id' => $origen->servicioId,
                    'nombre' => $this->nombres->servicio($origen->servicioId),
                ],
                'sala' => $origen->salaId === null ? null : [
                    'id' => $origen->salaId,
                    'nombre' => $this->nombres->sala($origen->salaId),
                ],
                'profesional' => $origen->patron?->profesionalId === null || $origen->patron === null
                    ? null
                    : [
                        'id' => $origen->patron->profesionalId,
                        'nombre' => $this->nombres->profesional($origen->patron->profesionalId),
                    ],
            ]),
            'turnos_existentes' => count($existentes),
            'puede_generar' => $origen->estadoEfectivo->value === 'activa'
                && $origen->patronDisponible()
                && $activos === 0,
            'puede_regenerar' => $origen->estadoEfectivo->value === 'activa'
                && $origen->patronDisponible()
                && $activos > 0,
        ];
    }
}
