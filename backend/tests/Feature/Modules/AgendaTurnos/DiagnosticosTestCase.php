<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\MotivoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnosticoRequisito;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\Sanctum;

abstract class DiagnosticosTestCase extends ProgramacionTurnosTestCase
{
    /**
     * @param  list<string>  $extra
     * @param  list<string>  $without
     */
    protected function actorDiagnosticos(
        Branch $branch,
        array $extra = [],
        array $without = [],
    ): User {
        $permissions = array_values(array_diff([
            'asignacion.ver',
            'asignacion.crear',
            'asignacion.exportar',
            'diagnostico_agenda.cancelar',
            'diagnostico_agenda.actualizar_estado',
            'tutor.ver_contacto',
            'turno.ver',
            'turno.reprogramar',
            'turno.cancelar',
            'asistencia.ver',
            'asistencia.registrar',
            'atencion.ver',
            'paciente.ver',
            ...$extra,
        ], $without));

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $user->branches()->sync([$branch->id]);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function diagnosticosUrl(string $sufijo = ''): string
    {
        return '/api/agenda-turnos/diagnosticos'.$sufijo;
    }

    protected function viajarA(string $fechaHora): void
    {
        Carbon::setTestNow(Carbon::parse($fechaHora, 'America/La_Paz'));
        CarbonImmutable::setTestNow(CarbonImmutable::parse($fechaHora, 'America/La_Paz'));
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function actorConPermisos(Branch $branch, array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $user->branches()->sync([$branch->id]);
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * @param  list<Especialidad>  $especialidades
     * @param  list<Profesional>  $profesionales
     */
    protected function crearDiagnosticoEnSucursal(
        Branch $branch,
        Paciente $paciente,
        int $servicioId,
        TipoDiagnostico $tipo,
        array $especialidades,
        array $profesionales,
    ): AtencionDiagnostico {
        $categoria = $this->categoriaTarifa();
        $tarifa = $this->tarifa(
            Servicio::query()->findOrFail($servicioId),
            $branch,
            $categoria,
        );

        $diagnostico = AtencionDiagnostico::query()->create([
            'paciente_id' => $paciente->id,
            'sucursal_id' => $branch->id,
            'servicio_id' => $servicioId,
            'tipo_diagnostico_id' => $tipo->id,
            'tarifa_id' => $tarifa->id,
            'categoria_tarifa_id' => $categoria->id,
            'fecha_inicio' => '2025-11-01',
            'fecha_fin' => '2025-12-31',
            'monto_esperado' => '500.00',
            'entrevista_previa_id' => null,
            'omitio_entrevista' => true,
            'motivo_omision_entrevista' => null,
            'estado' => 'activa',
            'notas_admin' => null,
        ]);

        foreach ($especialidades as $especialidad) {
            AtencionDiagnosticoRequisito::query()->insert([
                'atencion_diagnostico_id' => $diagnostico->id,
                'especialidad_id' => $especialidad->id,
                'especialidad_nombre' => $especialidad->nombre,
                'cantidad_minima' => 1,
                'created_at' => now(),
            ]);
        }

        $diagnostico->profesionales()->sync(array_map(
            static fn (Profesional $profesional): int => (int) $profesional->id,
            $profesionales,
        ));

        return $diagnostico;
    }

    /**
     * @return array<string, mixed>
     */
    protected function escenarioDiagnostico(int $cantidadProfesionales = 2): array
    {
        $base = $this->escenario();
        $branch = $base['branch'];
        $actor = $this->actorDiagnosticos($branch);
        $servicio = $this->servicio('diagnostico_evaluacion');
        $tipo = TipoDiagnostico::query()->create([
            'servicio_id' => $servicio->id,
            'nombre' => 'Evaluación integral '.uniqid(),
            'descripcion' => null,
            'dias_estimados' => 5,
            'activo' => true,
            'version' => 1,
        ]);

        $especialidades = [];
        $profesionales = [];

        for ($i = 0; $i < $cantidadProfesionales; $i++) {
            $especialidad = Especialidad::query()->create([
                'nombre' => 'Especialidad '.$i.' '.uniqid(),
                'descripcion' => null,
                'activo' => true,
                'version' => 1,
            ]);
            $especialidades[] = $especialidad;
            $tipo->especialidades()->attach($especialidad->id, ['cantidad_minima' => 1]);

            $profesional = Profesional::query()->create([
                'nombre_completo' => 'Profesional diagnóstico '.$i.' '.uniqid(),
                'ci' => (string) random_int(10000000, 99999999),
                'activo' => true,
                'version' => 1,
            ]);
            ProfesionalEspecialidad::query()->create([
                'profesional_id' => $profesional->id,
                'especialidad_id' => $especialidad->id,
                'tipo' => 'principal',
                'activo' => true,
            ]);
            ProfesionalSucursal::query()->create([
                'profesional_id' => $profesional->id,
                'sucursal_id' => $branch->id,
                'activo' => true,
                'version' => 1,
            ]);
            $profesionales[] = $profesional;
        }

        $diagnostico = AtencionDiagnostico::query()->create([
            'paciente_id' => $base['paciente']->id,
            'sucursal_id' => $branch->id,
            'servicio_id' => $servicio->id,
            'tipo_diagnostico_id' => $tipo->id,
            'tarifa_id' => $base['tarifa']->id,
            'categoria_tarifa_id' => $base['categoria']->id,
            'fecha_inicio' => '2025-11-01',
            'fecha_fin' => '2025-12-31',
            'monto_esperado' => '500.00',
            'entrevista_previa_id' => null,
            'omitio_entrevista' => true,
            'motivo_omision_entrevista' => null,
            'estado' => 'activa',
            'notas_admin' => null,
        ]);

        foreach ($especialidades as $especialidad) {
            AtencionDiagnosticoRequisito::query()->insert([
                'atencion_diagnostico_id' => $diagnostico->id,
                'especialidad_id' => $especialidad->id,
                'especialidad_nombre' => $especialidad->nombre,
                'cantidad_minima' => 1,
                'created_at' => now(),
            ]);
        }

        $diagnostico->profesionales()->sync(array_map(
            static fn (Profesional $profesional): int => (int) $profesional->id,
            $profesionales,
        ));

        return [
            ...$base,
            'actor' => $actor,
            'servicio_diagnostico' => $servicio,
            'tipo' => $tipo,
            'especialidades' => $especialidades,
            'profesionales_diagnostico' => $profesionales,
            'diagnostico' => $diagnostico,
        ];
    }

    protected function motivo(string $codigo): MotivoTurno
    {
        return MotivoTurno::query()->where('codigo', $codigo)->firstOrFail();
    }

    protected function crearTurnoDiagnostico(
        AtencionDiagnostico $diagnostico,
        int $profesionalId,
        string $fecha = '2025-12-02',
        string $inicio = '09:00',
        string $fin = '09:50',
        EstadoTurno $estado = EstadoTurno::PROGRAMADO,
    ): Turno {
        return Turno::factory()
            ->paraDiagnostico($diagnostico)
            ->paraFecha($fecha)
            ->paraHorario($inicio, $fin)
            ->conProfesional($profesionalId)
            ->estado($estado)
            ->create();
    }

    protected function crearTurnoDeOtroPaciente(
        Branch $branch,
        Paciente $paciente,
        int $servicioId,
        int $profesionalId,
        string $fecha = '2025-12-02',
        string $inicio = '09:00',
        string $fin = '09:50',
    ): Turno {
        $sala = $this->sala($branch);

        return Turno::factory()
            ->paraSala($sala)
            ->paraPaciente($paciente->id)
            ->paraServicio($servicioId)
            ->paraFecha($fecha)
            ->paraHorario($inicio, $fin)
            ->conProfesional($profesionalId)
            ->create(['sucursal_id' => $branch->id]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadProgramacion(int $profesionalId, string $fecha = '2025-12-02', string $hora = '09:00'): array
    {
        return [
            'profesional_id' => $profesionalId,
            'fecha' => $fecha,
            'hora_inicio' => $hora,
        ];
    }
}
