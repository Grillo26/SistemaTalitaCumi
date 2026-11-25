<?php

namespace App\Modules\Pacientes\Exceptions;

/**
 * Coincidencia parcial confirmable (04.01/RF-016).
 */
final class PotentialDuplicatePacienteException extends PacientesDomainException
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data = [])
    {
        parent::__construct('Se encontraron posibles duplicados. Confirmá si es el mismo paciente o si se trata de otra persona.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'POTENTIAL_DUPLICATE';
    }

    /**
     * @return array<string, mixed>
     */
    protected function errorContext(): array
    {
        return ['data' => $this->data];
    }
}
