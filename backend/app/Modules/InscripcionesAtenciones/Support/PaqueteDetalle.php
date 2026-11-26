<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\Pacientes\Contracts\PacienteResumen;
use App\Modules\Pacientes\Contracts\TutorResumen;

/**
 * Datos de la ficha de un paquete.
 */
final readonly class PaqueteDetalle
{
    /**
     * @param  array{id: int, nombre: string}  $sucursal
     * @param  array{id: int, nombre: string, tipo_servicio: ?string}  $servicio
     * @param  array{id: int, nombre: string}  $categoria
     * @param  array{id: int, nombre: string}|null  $profesional
     * @param  array<string, mixed>|null  $retiro
     */
    public function __construct(
        public PaqueteSesiones $paquete,
        public PacienteResumen $paciente,
        public TutorResumen $tutor,
        public array $sucursal,
        public array $servicio,
        public array $categoria,
        public ?array $profesional = null,
        public ?array $retiro = null,
        public ?PaqueteSesiones $origen = null,
        public ?PaqueteSesiones $renovadaPor = null,
    ) {}
}
