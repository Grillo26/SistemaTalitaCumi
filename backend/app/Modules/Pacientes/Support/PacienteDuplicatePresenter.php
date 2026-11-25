<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Pacientes\Models\Paciente;

/**
 * Presentación sanitizada de duplicados (04.01/CA-002, 04.01/RF-016).
 *
 * Nunca expone datos clínicos. Los datos identificatorios del menor solo se
 * devuelven si está dentro del alcance del actor.
 */
final class PacienteDuplicatePresenter
{
    public function __construct(private readonly PacienteAccessScope $scope) {}

    /**
     * @return array<string, mixed>
     */
    public function hard(User $actor, Paciente $existing): array
    {
        if (! $this->scope->canAccess($actor, $existing)) {
            return [
                'coincide_documento' => true,
                'fuera_de_alcance' => true,
                'paciente_existente' => null,
            ];
        }

        return [
            'coincide_documento' => true,
            'fuera_de_alcance' => false,
            'paciente_existente' => $this->minimal($existing),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function soft(User $actor, PacienteDuplicateResult $result): array
    {
        $inScope = [];
        $hasOutOfScope = false;

        foreach ($result->softMatches as $match) {
            /** @var Paciente $paciente */
            $paciente = $match['paciente'];

            if ($this->scope->canAccess($actor, $paciente)) {
                $inScope[] = array_merge($this->minimal($paciente), [
                    'tipo_coincidencia' => $match['tipo']->value,
                    'tipo_coincidencia_label' => $match['tipo']->label(),
                ]);
            } else {
                $hasOutOfScope = true;
            }
        }

        return [
            'requiere_confirmacion' => true,
            'coincidencias' => $inScope,
            'coincidencias_fuera_de_alcance' => $hasOutOfScope,
        ];
    }

    /**
     * @return list<int>
     */
    public function inScopeIds(User $actor, PacienteDuplicateResult $result): array
    {
        return array_values(array_map(
            static fn (array $match): int => (int) $match['paciente']->id,
            array_filter(
                $result->softMatches,
                fn (array $match): bool => $this->scope->canAccess($actor, $match['paciente']),
            ),
        ));
    }

    public function hasOutOfScope(User $actor, PacienteDuplicateResult $result): bool
    {
        foreach ($result->softMatches as $match) {
            if (! $this->scope->canAccess($actor, $match['paciente'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{id: int, codigo: ?string, nombre_completo: string, fecha_nacimiento: string, estado: string, estado_label: string, puede_ver_ficha: bool}
     */
    private function minimal(Paciente $paciente): array
    {
        return [
            'id' => (int) $paciente->id,
            'codigo' => $paciente->codigo,
            'nombre_completo' => $paciente->nombre_completo,
            'fecha_nacimiento' => $paciente->fecha_nacimiento->toDateString(),
            'estado' => $paciente->estado->value,
            'estado_label' => $paciente->estado->label(),
            'puede_ver_ficha' => true,
        ];
    }
}
