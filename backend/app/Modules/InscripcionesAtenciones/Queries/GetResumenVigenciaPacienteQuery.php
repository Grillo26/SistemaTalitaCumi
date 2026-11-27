<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\EstadoVigencia;
use App\Modules\InscripcionesAtenciones\Rules\VigenciaClassifierRule;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPeriod;
use App\Modules\InscripcionesAtenciones\Support\VigenciaQueryBuilder;
use App\Modules\InscripcionesAtenciones\Support\VigenciaSaldoResolver;
use App\Modules\InscripcionesAtenciones\Support\VigenciaSettings;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use Illuminate\Support\Collection;

/**
 * [Vigencia y Renovación] RF-015 / CU-03 FE-01.
 */
final class GetResumenVigenciaPacienteQuery
{
    public function __construct(
        private readonly VigenciaQueryBuilder $builder,
        private readonly VigenciaSaldoResolver $saldo,
        private readonly VigenciaSettings $settings,
        private readonly PatientDirectoryContract $patients,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $pacienteId, User $actor): array
    {
        $paciente = $this->patients->resumen($pacienteId);
        $ventana = $this->settings->ventanaAlertaDias();
        $hoy = VigenciaPeriod::today();

        $rows = collect($this->builder->all(
            filters: ['paciente_id' => $pacienteId],
            actor: $actor,
            ventanaDias: $ventana,
            hoy: $hoy,
            limit: 500,
        ));

        $this->saldo->enrich($rows);

        return [
            'paciente' => $paciente->toArray(),
            'conteos' => $this->conteos($rows, $ventana, $hoy),
            'items' => $rows,
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, int>
     */
    private function conteos(Collection $rows, int $ventana, string $hoy): array
    {
        $conteos = array_fill_keys(EstadoVigencia::values(), 0);

        foreach ($rows as $row) {
            $estado = VigenciaClassifierRule::classify(
                fechaFin: (string) $row->fecha_fin,
                hoy: $hoy,
                ventanaDias: $ventana,
                retirada: (bool) $row->retirada,
                renovada: (bool) $row->renovada,
            );

            $conteos[$estado->value]++;
        }

        return $conteos;
    }
}
