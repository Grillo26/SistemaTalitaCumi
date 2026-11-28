<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\Organizacion\Models\Branch;
use Laravel\Sanctum\Sanctum;

abstract class AsistenciaTurnosTestCase extends CambiosTurnosTestCase
{
    /**
     * @param  list<string>  $extra
     * @param  list<string>  $without
     */
    protected function actorAsistencia(
        Branch $branch,
        array $extra = [],
        array $without = [],
    ): User {
        $permissions = array_values(array_diff([
            'turno.ver',
            'turno.historial',
            'asistencia.ver',
            'asistencia.registrar',
            'asistencia.ver_indicadores',
            'asistencia.resumen_diario',
            'paquete.ver',
            'inscripcion.ver',
            ...$extra,
        ], $without));

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $user->branches()->sync([$branch->id]);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function asistenciaUrl(string $sufijo): string
    {
        return '/api/agenda-turnos/asistencia'.$sufijo;
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadRegistro(string $estado, ?int $version = null): array
    {
        return [
            'estado' => $estado,
            ...($version === null ? [] : ['version' => $version]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadCorreccion(
        string $estado,
        int $version,
        ?string $motivo = null,
    ): array {
        return [
            'estado' => $estado,
            'version' => $version,
            'motivo' => $motivo,
        ];
    }

    protected function registrar(int $turnoId, EstadoTurno $estado): void
    {
        $this->postJson(
            $this->asistenciaUrl("/turnos/{$turnoId}/registrar"),
            $this->payloadRegistro($estado->value),
        )->assertOk();
    }

    protected function crearPaqueteVigente(array $escenario): PaqueteSesiones
    {
        return $this->crearPaquete($escenario, [
            'fecha_inicio' => '2025-11-01',
            'fecha_fin' => '2025-11-30',
            'hora' => '09:00',
        ]);
    }

    protected function turnoPaqueteHoy(
        PaqueteSesiones $paquete,
        string $inicio = '09:00',
        string $fin = '09:50',
    ): Turno {
        return $this->crearTurnoPaquete($paquete, '2025-11-30', $inicio, $fin);
    }
}
