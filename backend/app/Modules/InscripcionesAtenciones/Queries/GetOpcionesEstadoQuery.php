<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Modules\InscripcionesAtenciones\Enums\AccionEstado;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Support\EstadoMotivoCatalog;
use App\Modules\Pacientes\Enums\EstadoPaciente;

/**
 * Opciones de estado y motivos para la UI.
 */
final class GetOpcionesEstadoQuery
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $motivos = [
            'paciente' => [
                'pausar' => EstadoMotivoCatalog::opciones(AccionEstado::PAUSAR),
                'retirar' => EstadoMotivoCatalog::opciones(AccionEstado::RETIRAR),
            ],
            TipoEntidadVigencia::INSCRIPCION_SALA->value => [
                'pausar' => EstadoMotivoCatalog::opciones(AccionEstado::PAUSAR, TipoEntidadVigencia::INSCRIPCION_SALA),
                'retirar' => EstadoMotivoCatalog::opciones(AccionEstado::RETIRAR, TipoEntidadVigencia::INSCRIPCION_SALA),
            ],
            TipoEntidadVigencia::PAQUETE_SESIONES->value => [
                'pausar' => EstadoMotivoCatalog::opciones(AccionEstado::PAUSAR, TipoEntidadVigencia::PAQUETE_SESIONES),
                'retirar' => EstadoMotivoCatalog::opciones(AccionEstado::RETIRAR, TipoEntidadVigencia::PAQUETE_SESIONES),
            ],
        ];

        return [
            'estados_paciente' => array_map(
                static fn (EstadoPaciente $estado): array => [
                    'value' => $estado->value,
                    'label' => $estado->label(),
                ],
                EstadoPaciente::cases(),
            ),
            'estados_inscripcion' => EstadoInscripcion::options(),
            'acciones' => array_map(
                static fn (AccionEstado $accion): array => [
                    'value' => $accion->value,
                    'label' => $accion->label(),
                ],
                AccionEstado::cases(),
            ),
            'motivos' => $motivos,
        ];
    }
}
