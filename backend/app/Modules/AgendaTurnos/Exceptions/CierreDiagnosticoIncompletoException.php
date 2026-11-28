<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-012 / 07.05/RN-005 / 07.05/CA-003.
 *
 * El cierre a "Completo" exige que todos los puestos requeridos tengan al
 * menos un turno en estado "asistió".
 */
final class CierreDiagnosticoIncompletoException extends AgendaTurnosDomainException
{
    /**
     * @param  list<array{tipo: string, especialidad_id: int, mensaje: string}>  $faltantes
     */
    public function __construct(public readonly array $faltantes)
    {
        parent::__construct('No se puede cerrar el diagnóstico: faltan asistencias requeridas.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CIERRE_DIAGNOSTICO_INCOMPLETO';
    }

    public function context(): array
    {
        return ['faltantes' => $this->faltantes];
    }
}
