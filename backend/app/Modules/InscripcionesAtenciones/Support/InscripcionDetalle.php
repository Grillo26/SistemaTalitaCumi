<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\Pacientes\Contracts\PacienteResumen;
use App\Modules\Pacientes\Contracts\TutorResumen;

/**
 * Datos de la ficha de una inscripción.
 */
final readonly class InscripcionDetalle
{
    /**
     * @param  array{id: int, nombre: string, tipo_sala: ?string, turno: ?string}  $sala
     * @param  array{id: int, nombre: string, tipo_servicio: ?string}  $servicio
     * @param  array{id: int, nombre: string}  $categoria
     * @param  array{id: int, nombre: string}  $sucursal
     * @param  array<string, mixed>|null  $retiro
     */
    public function __construct(
        public InscripcionSala $inscripcion,
        public PacienteResumen $paciente,
        public TutorResumen $tutor,
        public array $sala,
        public array $servicio,
        public array $categoria,
        public array $sucursal,
        public ?array $retiro = null,
        public ?InscripcionSala $origen = null,
        public ?InscripcionSala $renovadaPor = null,
    ) {}
}
