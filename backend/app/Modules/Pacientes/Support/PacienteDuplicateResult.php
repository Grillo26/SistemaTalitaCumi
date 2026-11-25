<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Enums\TipoCoincidencia;
use App\Modules\Pacientes\Models\Paciente;

/**
 * Resultado interno de la detección de duplicados.
 */
final readonly class PacienteDuplicateResult
{
    /**
     * @param  list<array{paciente: Paciente, tipo: TipoCoincidencia}>  $softMatches
     */
    public function __construct(
        public ?Paciente $hardDocument,
        public array $softMatches,
    ) {}

    public function hasSoftMatches(): bool
    {
        return $this->softMatches !== [];
    }

    /**
     * @return list<int>
     */
    public function softMatchIds(): array
    {
        return array_map(
            static fn (array $match): int => (int) $match['paciente']->id,
            $this->softMatches,
        );
    }
}
