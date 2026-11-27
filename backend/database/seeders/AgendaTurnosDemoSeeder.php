<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Profesionales\Models\Profesional;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración SOLO para desarrollo local.
 *
 * No está registrado en DatabaseSeeder. No crea pacientes, profesionales,
 * salas ni servicios: usa los que ya existen. Si faltan datos o ya hay
 * turnos en la semana actual, no hace nada.
 *
 * Uso:
 *   php artisan db:seed --class=AgendaTurnosDemoSeeder
 */
final class AgendaTurnosDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('AgendaTurnosDemoSeeder solo se ejecuta con APP_ENV=local.');

            return;
        }

        $hoy = CarbonImmutable::now('America/La_Paz')->startOfDay();
        $inicio = $hoy->startOfWeek(CarbonInterface::MONDAY);
        $branch = Branch::query()
            ->where('activo', true)
            ->whereIn(
                'id',
                Sala::query()->where('activo', true)->select('sucursal_id'),
            )
            ->orderBy('nombre')
            ->first();

        if ($branch === null) {
            $this->command?->warn(
                'No hay sucursales activas con salas activas; no se generan turnos de demo.',
            );

            return;
        }

        $yaHayTurnos = Turno::query()
            ->where('sucursal_id', $branch->id)
            ->whereBetween('fecha', [$inicio->toDateString(), $inicio->addDays(6)->toDateString()])
            ->exists();

        if ($yaHayTurnos) {
            $this->command?->info('La sucursal ya tiene turnos esta semana; no se duplica la demo.');

            return;
        }

        $pacientes = Paciente::query()->where('estado', 'activo')->orderBy('id')->get();
        $salas = Sala::query()
            ->where('sucursal_id', $branch->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $servicios = Servicio::query()->where('activo', true)->orderBy('nombre')->get();
        $profesionales = Profesional::query()
            ->where('activo', true)
            ->whereHas('relacionesSucursal', static fn ($query) => $query
                ->where('sucursal_id', $branch->id)
                ->where('activo', true))
            ->orderBy('nombre_completo')
            ->get();

        if ($pacientes->isEmpty() || $salas->isEmpty() || $servicios->isEmpty()) {
            $this->command?->warn(
                'Se necesitan al menos un paciente activo, una sala activa y un servicio activo para la demo.',
            );

            return;
        }

        $horarios = [
            ['08:00', '08:50'],
            ['09:00', '09:50'],
            ['10:00', '10:50'],
            ['11:00', '11:50'],
        ];

        $creados = 0;
        $orden = 0;

        for ($dia = 0; $dia < 6; $dia++) {
            $fecha = $inicio->addDays($dia);

            foreach ($horarios as [$horaInicio, $horaFin]) {
                $sala = $salas[$orden % $salas->count()];
                $servicio = $servicios->firstWhere('area_id', $sala->area_id) ?? $servicios[0];
                $paciente = $pacientes[$orden % $pacientes->count()];
                $profesional = $profesionales->isEmpty()
                    ? null
                    : $profesionales[$orden % $profesionales->count()];

                $estado = $fecha->lessThan($hoy) ? ($orden % 3 === 0 ? 'falto' : 'asistio') : 'programado';

                $turno = Turno::query()->firstOrCreate(
                    [
                        'sucursal_id' => $branch->id,
                        'paciente_id' => $paciente->id,
                        'fecha' => $fecha->toDateString(),
                        'hora_inicio' => $horaInicio,
                    ],
                    [
                        'sala_id' => $sala->id,
                        'paquete_sesiones_id' => null,
                        'atencion_diagnostico_id' => null,
                        'servicio_id' => $servicio->id,
                        'profesional_id' => $profesional?->id,
                        'hora_fin' => $horaFin,
                        'duracion_minutos' => 50,
                        'estado' => $estado,
                        'motivo_cancelacion' => null,
                        'turno_origen_id' => null,
                        'usuario_registro_id' => null,
                        'usuario_asistencia_id' => null,
                        'asistencia_registrada_en' => null,
                    ],
                );

                if ($turno->wasRecentlyCreated) {
                    $creados++;
                }

                $orden++;
            }
        }

        $this->command?->info("AgendaTurnosDemoSeeder: {$creados} turnos creados en {$branch->nombre}.");
    }
}
