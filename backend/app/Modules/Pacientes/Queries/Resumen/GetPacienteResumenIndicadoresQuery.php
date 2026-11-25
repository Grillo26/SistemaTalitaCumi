<?php

namespace App\Modules\Pacientes\Queries\Resumen;

use App\Models\User;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Pacientes\Exceptions\PacienteResumenSucursalInactivaException;
use App\Modules\Pacientes\Exceptions\PacienteResumenSucursalInvalidaException;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteResumenBlocksBuilder;

/**
 * Refresco manual de indicadores de la ficha resumen (04.04/RF-015).
 *
 * No devuelve datos del paciente ni de tutores/contactos. No se audita:
 * la apertura de la ficha resumen ya quedó auditada. No usa caché.
 */
final class GetPacienteResumenIndicadoresQuery
{
    public function __construct(
        private readonly PacienteAccessScope $scope,
        private readonly BranchDirectoryContract $branches,
        private readonly PacienteResumenBlocksBuilder $indicators,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        Paciente $paciente,
        User $actor,
        ?int $sucursalId = null,
    ): array {
        $sucursalesAccesibles = $this->scope->accessibleBranchIds($actor);
        $this->assertSucursalContexto($sucursalId, $sucursalesAccesibles);

        $indicadores = $this->indicators->indicators(
            $paciente,
            $actor,
            $sucursalesAccesibles,
            $sucursalId,
        );

        return [
            'bloques' => $indicadores['bloques'],
            'contexto_sucursal' => $sucursalId,
            'sucursales_accesibles' => $sucursalesAccesibles,
            'fecha_corte' => $indicadores['fecha_corte'],
            'generado_en' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     */
    private function assertSucursalContexto(?int $sucursalId, ?array $sucursalesAccesibles): void
    {
        if ($sucursalId === null) {
            return;
        }

        if ($sucursalesAccesibles !== null && ! in_array($sucursalId, $sucursalesAccesibles, true)) {
            throw new PacienteResumenSucursalInvalidaException;
        }

        if (! $this->branches->estaActiva($sucursalId)) {
            throw new PacienteResumenSucursalInactivaException;
        }
    }
}
