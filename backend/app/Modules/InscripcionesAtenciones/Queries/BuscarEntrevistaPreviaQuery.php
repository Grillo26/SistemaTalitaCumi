<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\InscripcionesAtenciones\Support\AtencionQueryJoins;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;

/**
 * Aviso de entrevistas previas del paciente (CU-03 FE-01).
 *
 * No bloquea el alta del diagnóstico; solo informa.
 */
final class BuscarEntrevistaPreviaQuery
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly PatientDirectoryContract $patients,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(int $pacienteId, User $actor): array
    {
        $this->patients->resumen($pacienteId);

        $accessible = $this->access->accesibles($actor->id);

        if ($accessible === []) {
            return [];
        }

        return AtencionQueryJoins::entrevistas(AtencionEntrevista::query())
            ->where('atencion_entrevista.paciente_id', $pacienteId)
            ->where('atencion_entrevista.estado', 'activa')
            ->whereIn('atencion_entrevista.sucursal_id', $accessible)
            ->orderByDesc('atencion_entrevista.fecha')
            ->limit(20)
            ->get()
            ->map(static fn (AtencionEntrevista $entrevista): array => [
                'id' => (int) $entrevista->id,
                'fecha' => $entrevista->fecha->toDateString(),
                'sucursal' => [
                    'id' => (int) $entrevista->sucursal_id,
                    'nombre' => (string) ($entrevista->sucursal_nombre ?? ''),
                ],
            ])
            ->values()
            ->all();
    }
}
