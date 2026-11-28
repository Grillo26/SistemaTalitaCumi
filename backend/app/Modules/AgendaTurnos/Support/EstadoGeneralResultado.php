<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;

/**
 * Resultado puro del cálculo del estado general de un diagnóstico.
 */
final readonly class EstadoGeneralResultado
{
    /**
     * @param  list<array{
     *     especialidad_id: int,
     *     especialidad_nombre: string,
     *     cantidad_minima: int,
     *     asistidos: int,
     *     vigentes: int,
     *     cubierto: bool
     * }>  $puestos
     * @param  list<array{tipo: string, especialidad_id: int, mensaje: string}>  $faltantes
     */
    public function __construct(
        public EstadoGeneralDiagnostico $estado,
        public array $puestos,
        public array $faltantes,
    ) {}

    public function completo(): bool
    {
        return $this->estado === EstadoGeneralDiagnostico::COMPLETO;
    }

    /**
     * @return list<array{tipo: string, especialidad_id: int, mensaje: string}>
     */
    public function faltantesSinTurno(): array
    {
        return array_values(array_filter(
            $this->faltantes,
            static fn (array $faltante): bool => $faltante['tipo'] === 'sin_turno',
        ));
    }

    /**
     * @return list<array{tipo: string, especialidad_id: int, mensaje: string}>
     */
    public function faltantesSinAsistencia(): array
    {
        return array_values(array_filter(
            $this->faltantes,
            static fn (array $faltante): bool => $faltante['tipo'] === 'sin_asistencia',
        ));
    }
}
