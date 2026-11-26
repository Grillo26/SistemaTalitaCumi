<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPeriod;
use App\Modules\InscripcionesAtenciones\Support\VigenciaQueryBuilder;
use App\Modules\InscripcionesAtenciones\Support\VigenciaSaldoResolver;
use App\Modules\InscripcionesAtenciones\Support\VigenciaSettings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * [Vigencia y Renovación] RF-008 / RF-009 / CA-008 / CA-009.
 */
final class ListVencimientosReporteQuery
{
    public function __construct(
        private readonly VigenciaQueryBuilder $builder,
        private readonly VigenciaSaldoResolver $saldo,
        private readonly VigenciaSettings $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(
        array $filters,
        User $actor,
        int $page = 1,
        int $perPage = 25,
        string $sort = 'fecha_fin',
        string $direction = 'asc',
    ): LengthAwarePaginator {
        $ventana = $this->settings->ventanaAlertaDias();
        $hoy = VigenciaPeriod::today();

        $paginator = $this->builder->paginate(
            filters: $filters,
            actor: $actor,
            ventanaDias: $ventana,
            hoy: $hoy,
            page: $page,
            perPage: $perPage,
            sort: $sort,
            direction: $direction,
            panelDefault: false,
        );

        $this->saldo->enrich($paginator->getCollection());

        return $paginator;
    }
}
