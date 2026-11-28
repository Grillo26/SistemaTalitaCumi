<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Datos mínimos de un diagnóstico expuestos al módulo 07.05.
 *
 * No incluye datos clínicos, montos ni notas administrativas. Los nombres de
 * paciente, sucursal y profesionales se resuelven en el módulo consumidor con
 * sus propios contratos.
 */
final readonly class DiagnosticoAtencionResumen
{
    /**
     * @param  list<array{id: int, nombre_completo: string, especialidades: list<array{id: int, nombre: string}>}>  $profesionales
     * @param  list<array{especialidad_id: int, especialidad_nombre: string, cantidad_minima: int}>  $requisitos
     */
    public function __construct(
        public int $id,
        public int $pacienteId,
        public int $sucursalId,
        public int $servicioId,
        public string $servicioNombre,
        public int $tipoDiagnosticoId,
        public string $tipoDiagnosticoNombre,
        public string $fechaInicio,
        public string $fechaFin,
        public string $estado,
        public string $estadoGeneral,
        public array $profesionales,
        public array $requisitos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'paciente_id' => $this->pacienteId,
            'sucursal_id' => $this->sucursalId,
            'servicio_id' => $this->servicioId,
            'servicio_nombre' => $this->servicioNombre,
            'tipo_diagnostico_id' => $this->tipoDiagnosticoId,
            'tipo_diagnostico_nombre' => $this->tipoDiagnosticoNombre,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'estado' => $this->estado,
            'estado_general' => $this->estadoGeneral,
            'profesionales' => $this->profesionales,
            'requisitos' => $this->requisitos,
        ];
    }
}
