<?php

namespace Database\Factories;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Pacientes\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turno>
 */
class TurnoFactory extends Factory
{
    protected $model = Turno::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sala_id' => Sala::factory(),
            'sucursal_id' => static fn (array $attributes): int => (int) Sala::query()
                ->findOrFail($attributes['sala_id'])
                ->sucursal_id,
            'paciente_id' => Paciente::factory(),
            'paquete_sesiones_id' => null,
            'atencion_diagnostico_id' => null,
            'servicio_id' => Servicio::factory(),
            'profesional_id' => null,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '08:50',
            'duracion_minutos' => 50,
            'estado' => EstadoTurno::PROGRAMADO->value,
            'version' => 1,
            'motivo_cancelacion' => null,
            'turno_origen_id' => null,
            'usuario_registro_id' => null,
            'usuario_asistencia_id' => null,
            'asistencia_registrada_en' => null,
        ];
    }

    public function paraSala(Sala $sala): static
    {
        return $this->state(fn (): array => [
            'sala_id' => $sala->id,
            'sucursal_id' => $sala->sucursal_id,
            'paquete_sesiones_id' => null,
            'atencion_diagnostico_id' => null,
        ]);
    }

    public function paraPaquete(PaqueteSesiones $paquete): static
    {
        return $this->state(fn (): array => [
            'sala_id' => null,
            'sucursal_id' => $paquete->sucursal_id,
            'paciente_id' => $paquete->paciente_id,
            'paquete_sesiones_id' => $paquete->id,
            'atencion_diagnostico_id' => null,
            'servicio_id' => $paquete->servicio_id,
            'profesional_id' => $paquete->profesional_id,
        ]);
    }

    public function paraDiagnostico(AtencionDiagnostico $atencion): static
    {
        return $this->state(fn (): array => [
            'sala_id' => null,
            'sucursal_id' => $atencion->sucursal_id,
            'paciente_id' => $atencion->paciente_id,
            'paquete_sesiones_id' => null,
            'atencion_diagnostico_id' => $atencion->id,
            'servicio_id' => $atencion->servicio_id,
        ]);
    }

    public function sinProfesional(): static
    {
        return $this->state(fn (): array => ['profesional_id' => null]);
    }

    public function conProfesional(int $profesionalId): static
    {
        return $this->state(fn (): array => ['profesional_id' => $profesionalId]);
    }

    public function paraPaciente(int $pacienteId): static
    {
        return $this->state(fn (): array => ['paciente_id' => $pacienteId]);
    }

    public function paraServicio(int $servicioId): static
    {
        return $this->state(fn (): array => ['servicio_id' => $servicioId]);
    }

    public function paraSucursal(int $sucursalId): static
    {
        return $this->state(fn (): array => ['sucursal_id' => $sucursalId]);
    }

    public function paraFecha(string $fecha): static
    {
        return $this->state(fn (): array => ['fecha' => $fecha]);
    }

    public function paraHorario(string $horaInicio, string $horaFin): static
    {
        return $this->state(fn (): array => [
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
        ]);
    }

    public function estado(EstadoTurno $estado): static
    {
        return $this->state(fn (): array => ['estado' => $estado->value]);
    }
}
