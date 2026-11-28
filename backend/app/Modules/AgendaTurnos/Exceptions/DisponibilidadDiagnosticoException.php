<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-003 / 07.05/RN-003 / 07.05/CA-004.
 *
 * El profesional no está disponible: solapamiento, intervalo mínimo o
 * configuración de agenda.
 */
final class DisponibilidadDiagnosticoException extends AgendaTurnosDomainException
{
    /**
     * @param  list<string>  $motivos
     */
    public function __construct(public readonly array $motivos)
    {
        parent::__construct($motivos[0] ?? 'El profesional no está disponible en el horario elegido.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'DISPONIBILIDAD_DIAGNOSTICO';
    }

    public function context(): array
    {
        return ['motivos' => $this->motivos];
    }
}
