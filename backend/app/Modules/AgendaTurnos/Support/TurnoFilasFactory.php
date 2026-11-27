<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Models\User;
use App\Modules\AgendaTurnos\Models\TurnoLoteGeneracion;

/**
 * Construye las filas de `turno` que se insertan en lote.
 */
final class TurnoFilasFactory
{
    public function __construct(private readonly AgendaClock $clock) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function desdeCalculo(
        GeneracionCalculada $calculo,
        OrigenProgramacion $origen,
        PatronProgramacion $patron,
        TurnoLoteGeneracion $lote,
        User $actor,
    ): array {
        $ahora = $this->clock->ahora();
        $filas = [];

        foreach ($calculo->filasGenerables() as $fila) {
            $filas[] = [
                'sucursal_id' => $origen->sucursalId,
                'paciente_id' => $origen->pacienteId,
                'sala_id' => $origen->salaId,
                'inscripcion_sala_id' => $origen->esSala() ? $origen->id : null,
                'paquete_sesiones_id' => $origen->esPaquete() ? $origen->id : null,
                'atencion_diagnostico_id' => null,
                'servicio_id' => $origen->servicioId,
                'profesional_id' => $patron->profesionalId,
                'fecha' => $fila->fecha,
                'hora_inicio' => $fila->horaInicio,
                'hora_fin' => $fila->horaFin,
                'duracion_minutos' => $patron->duracionMinutos,
                'estado' => 'programado',
                'lote_generacion_id' => (int) $lote->id,
                'usuario_registro_id' => (int) $actor->id,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        return $filas;
    }
}
