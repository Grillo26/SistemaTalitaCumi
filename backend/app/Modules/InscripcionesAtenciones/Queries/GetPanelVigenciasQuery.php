<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPeriod;
use App\Modules\InscripcionesAtenciones\Support\VigenciaQueryBuilder;
use App\Modules\InscripcionesAtenciones\Support\VigenciaSaldoResolver;
use App\Modules\InscripcionesAtenciones\Support\VigenciaSettings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * [Vigencia y Renovación] RF-004 / CA-003 / RNF-008.
 */
final class GetPanelVigenciasQuery
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
        int $perPage = 20,
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
            panelDefault: true,
        );

        $this->saldo->enrich($paginator->getCollection());

        return $paginator;
    }
}
