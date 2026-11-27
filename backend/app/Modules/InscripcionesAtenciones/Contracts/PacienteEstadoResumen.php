<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\Pacientes\Enums\EstadoPaciente;

/**
 * Proyección administrativa mínima del paciente para el submódulo 06.05.
 */
final readonly class PacienteEstadoResumen
{
    public function __construct(
        public int $id,
        public ?string $codigo,
        public string $nombreCompleto,
        public EstadoPaciente $estado,
        public int $version,
        public int $sucursalRegistroId,
        public ?string $sucursalNombre = null,
        public ?string $tutorPrincipal = null,
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
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'version' => $this->version,
            'sucursal_registro' => [
                'id' => $this->sucursalRegistroId,
                'nombre' => $this->sucursalNombre,
            ],
            'tutor_principal' => $this->tutorPrincipal,
        ];
    }
}
