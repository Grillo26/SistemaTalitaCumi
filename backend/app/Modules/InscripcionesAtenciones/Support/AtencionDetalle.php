<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\Pacientes\Contracts\PacienteResumen;
use App\Modules\Pacientes\Contracts\TutorResumen;

/**
 * Datos comunes de la ficha de una atención.
 */
final readonly class AtencionDetalle
{
    /**
     * @param  array{id: int, nombre: string}  $sucursal
     * @param  array{id: int, nombre: string, tipo_servicio: ?string}  $servicio
     * @param  array{id: int, nombre: string}  $categoria
     * @param  array<string, mixed>|null  $tipoDiagnostico
     * @param  list<array<string, mixed>>  $profesionales
     * @param  list<array<string, mixed>>  $requisitos
     * @param  array<string, mixed>|null  $entrevistaPrevia
     * @param  array<string, mixed>|null  $retiro
     */
    public function __construct(
        public TipoAtencion $tipo,
        public AtencionEntrevista|AtencionDiagnostico $atencion,
        public PacienteResumen $paciente,
        public ?TutorResumen $tutor,
        public array $sucursal,
        public array $servicio,
        public array $categoria,
        public ?array $tipoDiagnostico,
        public array $profesionales,
        public array $requisitos,
        public ?array $entrevistaPrevia,
        public ?array $retiro,
    ) {}
}
