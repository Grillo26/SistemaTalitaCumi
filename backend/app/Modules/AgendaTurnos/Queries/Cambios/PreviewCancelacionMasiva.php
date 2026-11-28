<?php

namespace App\Modules\AgendaTurnos\Queries\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Rules\EfectoSaldoRule;
use App\Modules\AgendaTurnos\Support\CancelacionMasivaCalculator;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\AgendaTurnos\Support\TurnoExistente;

/**
 * [Reprogramación y Cancelación] RF-011 / RF-014 / RN-009 / CA-007.
 *
 * Previsualización de la cancelación masiva. No persiste. La ejecución
 * revalida el conjunto exacto de ids.
 */
final class PreviewCancelacionMasiva
{
    public function __construct(
        private readonly CancelacionMasivaCalculator $calculator,
        private readonly DirectoriosAgenda $nombres,
        private readonly EfectoSaldoRule $efecto,
    ) {}

    /**
     * @param  list<array{tipo: string, id: int}>  $entidades
     * @return array<string, mixed>
     */
    public function __invoke(
        int $pacienteId,
        array $entidades,
        ?int $servicioId,
        int $motivoId,
        ?string $motivoTexto,
        User $actor,
    ): array {
        $resultado = $this->calculator->calcular(
            $pacienteId,
            $entidades,
            $servicioId,
            $motivoId,
            $motivoTexto,
            $actor,
        );

        $elegibles = $this->enriquecer($resultado['clasificacion']->elegibles);

        return [
            'paciente_id' => $pacienteId,
            'entidades' => $resultado['entidades'],
            'sucursales' => $resultado['sucursales'],
            'elegibles' => $elegibles,
            'excluidos' => $resultado['excluidos'],
            'cantidades' => $resultado['cantidades'],
            'motivo' => [
                'id' => $resultado['motivo']->id,
                'codigo' => $resultado['motivo']->codigo,
                'nombre' => $resultado['motivo']->nombre,
                'origen' => $resultado['motivo']->origen?->value,
                'descuenta_saldo' => $resultado['motivo']->descuentaSaldo,
            ],
            'consume_saldo' => $resultado['motivo']->descuentaSaldo === true,
            'efecto_saldo' => $this->efecto->descripcion($resultado['motivo']->descuentaSaldo),
            'puede_confirmar' => $resultado['cantidades']['elegibles'] > 0,
        ];
    }

    /**
     * @param  list<TurnoExistente>  $turnos
     * @return list<array<string, mixed>>
     */
    private function enriquecer(array $turnos): array
    {
        $pacientes = $this->nombres->pacientes(array_map(
            static fn (TurnoExistente $turno): int => $turno->pacienteId,
            $turnos,
        ));
        $servicios = $this->nombres->servicios(array_map(
            static fn (TurnoExistente $turno): int => $turno->servicioId,
            $turnos,
        ));
        $profesionales = $this->nombres->profesionales(array_values(array_filter(array_map(
            static fn (TurnoExistente $turno): ?int => $turno->profesionalId,
            $turnos,
        ))));
        $salas = $this->nombres->salas(array_values(array_filter(array_map(
            static fn (TurnoExistente $turno): ?int => $turno->salaId,
            $turnos,
        ))));

        return array_map(
            static fn (TurnoExistente $turno): array => [
                ...$turno->toArray(),
                'paciente' => [
                    'id' => $turno->pacienteId,
                    'nombre' => $pacientes[$turno->pacienteId] ?? DirectoriosAgenda::NO_DISPONIBLE,
                ],
                'servicio' => [
                    'id' => $turno->servicioId,
                    'nombre' => $servicios[$turno->servicioId] ?? DirectoriosAgenda::NO_DISPONIBLE,
                ],
                'profesional' => $turno->profesionalId === null ? null : [
                    'id' => $turno->profesionalId,
                    'nombre' => $profesionales[$turno->profesionalId] ?? DirectoriosAgenda::NO_DISPONIBLE,
                ],
                'sala' => $turno->salaId === null ? null : [
                    'id' => $turno->salaId,
                    'nombre' => $salas[$turno->salaId] ?? DirectoriosAgenda::NO_DISPONIBLE,
                ],
            ],
            $turnos,
        );
    }
}
