<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-007 / 07.05/RF-012 / 07.05/RN-005.
 *
 * El estado general solicitado no es coherente con los turnos del
 * diagnóstico. El contexto incluye el estado calculado y los faltantes.
 */
final class EstadoGeneralInvalidoException extends AgendaTurnosDomainException
{
    /**
     * @param  list<array{tipo: string, especialidad_id: int, mensaje: string}>  $faltantes
     */
    public function __construct(
        public readonly string $solicitado,
        public readonly string $sugerido,
        public readonly array $faltantes,
    ) {
        parent::__construct('El estado general solicitado no es válido para los turnos actuales del diagnóstico.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ESTADO_GENERAL_INVALIDO';
    }

    public function context(): array
    {
        return [
            'estado_solicitado' => $this->solicitado,
            'estado_sugerido' => $this->sugerido,
            'faltantes' => $this->faltantes,
        ];
    }
}
