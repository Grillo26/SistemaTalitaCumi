<?php

namespace App\Modules\AgendaTurnos\Queries;

use App\Models\User;
use App\Modules\AgendaTurnos\Exceptions\TurnoNotFoundException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\AccionesDisponiblesRule;
use App\Modules\AgendaTurnos\Support\AccionesTurnoRegistry;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\AgendaTurnosPermission;
use App\Modules\AgendaTurnos\Support\DatosOperativosResolver;
use App\Modules\AgendaTurnos\Support\TurnoAgendaPresenter;
use App\Modules\Seguridad\Contracts\PermissionEvaluatorContract;
use Carbon\CarbonImmutable;

/**
 * [Agenda Semanal] RF-008, RF-009, CU-03.
 *
 * El detalle solo expone datos operativos. Las acciones se calculan con el
 * registry de 7.3/7.4; mientras no exista ninguna, devuelve una lista vacía
 * y un aviso explícito.
 */
final class GetTurnoDetalle
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly DatosOperativosResolver $datos,
        private readonly TurnoAgendaPresenter $presenter,
        private readonly AccionesDisponiblesRule $accionesRule,
        private readonly AccionesTurnoRegistry $registry,
        private readonly PermissionEvaluatorContract $permissions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $id, User $actor): array
    {
        $turno = Turno::query()->find($id);

        if ($turno === null) {
            throw new TurnoNotFoundException($id);
        }

        $this->access->assertSucursal($actor, (int) $turno->sucursal_id);

        $datos = $this->datos->resolver(
            [$turno->paciente_id],
            $turno->profesional_id === null ? [] : [$turno->profesional_id],
            $turno->sala_id === null ? [] : [$turno->sala_id],
            false,
        );

        $zona = (string) config('app.timezone', 'America/La_Paz');
        $esHoy = $turno->fecha->toDateString() === CarbonImmutable::now($zona)->toDateString();
        $permisos = $this->permissions->permisos($actor->id);
        $registradas = $turno->asistencia_registrada_en === null
            ? []
            : ['registrar_asistencia'];
        $acciones = ($this->accionesRule)(
            $turno->estado,
            $permisos,
            $this->registry->todas(),
            $registradas,
        );

        $mensaje = null;

        if ($acciones === []) {
            $mensaje = $this->registry->todas() === []
                ? 'Las acciones de asistencia, reprogramación y cancelación se habilitarán en los próximos submódulos.'
                : 'No tenés acciones disponibles para este turno.';
        }

        return [
            'data' => $this->presenter->presenteDetalle($turno, $datos, $esHoy),
            'acciones' => array_map(
                static fn ($accion): array => $accion->toArray(),
                $acciones,
            ),
            'acciones_disponibles' => $acciones !== [],
            'mensaje_acciones' => $mensaje,
            'can' => [
                'ver' => $this->permissions->puede(
                    $actor->id,
                    AgendaTurnosPermission::VER,
                    (int) $turno->sucursal_id,
                ),
                'exportar' => $this->permissions->puede(
                    $actor->id,
                    AgendaTurnosPermission::EXPORTAR,
                    (int) $turno->sucursal_id,
                ),
            ],
        ];
    }
}
