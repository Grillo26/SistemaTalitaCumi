<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\MotivoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Pacientes\Models\Paciente;
use Laravel\Sanctum\Sanctum;

abstract class CambiosTurnosTestCase extends ProgramacionTurnosTestCase
{
    protected function actorCambios(Branch $branch, array $extra = []): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'turno.ver',
            'turno.reprogramar',
            'turno.cancelar',
            'turno.cancelar_masivo',
            'turno.historial',
            'turno.reporte_cambios',
            'turno.exportar_cambios',
            'tutor.ver_contacto',
            'paquete.ver',
            'inscripcion.ver',
            ...$extra,
        ]);
        $user->branches()->sync([$branch->id]);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function crearTurnoPaquete(
        PaqueteSesiones $paquete,
        string $fecha = '2025-12-02',
        string $inicio = '09:00',
        string $fin = '09:50',
        EstadoTurno $estado = EstadoTurno::PROGRAMADO,
        ?int $profesionalId = null,
    ): Turno {
        $factory = Turno::factory()
            ->paraPaquete($paquete)
            ->paraFecha($fecha)
            ->paraHorario($inicio, $fin)
            ->estado($estado);

        if ($profesionalId !== null) {
            $factory = $factory->conProfesional($profesionalId);
        }

        return $factory->create();
    }

    protected function crearTurnoSala(
        Branch $branch,
        Sala $sala,
        Paciente $paciente,
        int $servicioId,
        string $fecha = '2025-12-02',
        string $inicio = '09:00',
        string $fin = '09:50',
        EstadoTurno $estado = EstadoTurno::PROGRAMADO,
        ?int $profesionalId = null,
        ?int $inscripcionSalaId = null,
    ): Turno {
        $factory = Turno::factory()
            ->paraSala($sala)
            ->paraPaciente($paciente->id)
            ->paraServicio($servicioId)
            ->paraFecha($fecha)
            ->paraHorario($inicio, $fin)
            ->estado($estado);

        if ($profesionalId !== null) {
            $factory = $factory->conProfesional($profesionalId);
        }

        return $factory->create([
            'sucursal_id' => $branch->id,
            'inscripcion_sala_id' => $inscripcionSalaId,
        ]);
    }

    protected function motivo(string $codigo): MotivoTurno
    {
        return MotivoTurno::query()->where('codigo', $codigo)->firstOrFail();
    }

    protected function cambiosUrl(string $sufijo): string
    {
        return '/api/agenda-turnos/cambios-turnos'.$sufijo;
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadReprogramacion(
        int $motivoId,
        string $fecha,
        string $hora,
        ?int $profesionalId = null,
        bool $confirmar = false,
    ): array {
        return [
            'fecha' => $fecha,
            'hora_inicio' => $hora,
            'motivo_id' => $motivoId,
            'estado_esperado' => 'programado',
            'profesional_id' => $profesionalId,
            'confirmar_advertencias' => $confirmar,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadCancelacion(int $motivoId): array
    {
        return [
            'motivo_id' => $motivoId,
            'estado_esperado' => 'programado',
        ];
    }

    /**
     * @param  list<array{tipo: string, id: int}>  $entidades
     * @return array<string, mixed>
     */
    protected function payloadMasiva(int $pacienteId, array $entidades, int $motivoId): array
    {
        return [
            'paciente_id' => $pacienteId,
            'entidades' => $entidades,
            'motivo_id' => $motivoId,
        ];
    }

    protected function crearInscripcionParaPaciente(
        Branch $branch,
        Sala $sala,
        Paciente $paciente,
        int $servicioId,
        int $tarifaId,
        int $categoriaId,
        string $inicio = '2025-12-01',
        string $fin = '2025-12-31',
    ): InscripcionSala {
        return InscripcionSala::query()->create([
            'paciente_id' => $paciente->id,
            'tutor_responsable_id' => $this->tutor($branch)->id,
            'sala_id' => $sala->id,
            'sucursal_id' => $branch->id,
            'servicio_id' => $servicioId,
            'tarifa_id' => $tarifaId,
            'categoria_tarifa_id' => $categoriaId,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'monto_esperado' => '500.00',
            'estado' => 'activa',
            'observaciones' => null,
        ]);
    }
}
