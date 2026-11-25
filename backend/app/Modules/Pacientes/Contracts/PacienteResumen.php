<?php

namespace App\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Enums\EstadoPaciente;

/**
 * Resumen no sensible del paciente para módulos consumidores.
 */
final readonly class PacienteResumen
{
    public function __construct(
        public int $id,
        public ?string $codigo,
        public string $nombreCompleto,
        public string $fechaNacimiento,
        public int $edadAnios,
        public EstadoPaciente $estado,
        public int $sucursalRegistroId,
        public ?int $tipoDiagnosticoId,
        public bool $sinDiagnosticoDefinido,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre_completo' => $this->nombreCompleto,
            'fecha_nacimiento' => $this->fechaNacimiento,
            'edad_anios' => $this->edadAnios,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'sucursal_registro_id' => $this->sucursalRegistroId,
            'tipo_diagnostico_id' => $this->tipoDiagnosticoId,
            'sin_diagnostico_definido' => $this->sinDiagnosticoDefinido,
        ];
    }
}
