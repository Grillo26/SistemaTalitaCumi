<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResolverContract;
use App\Modules\InscripcionesAtenciones\Rules\CantidadSesionesRule;
use App\Modules\InscripcionesAtenciones\Rules\MontoPaqueteRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteEstimacionRule;
use App\Modules\InscripcionesAtenciones\Support\PaqueteSettings;
use App\Modules\InscripcionesAtenciones\Support\TerapiaIndividualServicioGuard;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;

/**
 * RF-007 / RF-008: monto esperado del paquete antes de guardar.
 */
final class PreviewMontoPaqueteQuery
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly TarifaAplicableResolverContract $tarifas,
        private readonly TerapiaIndividualServicioGuard $servicios,
        private readonly PaqueteSettings $settings,
    ) {}

    /**
     * @param  list<int>|null  $diasSemana
     * @return array<string, mixed>
     */
    public function __invoke(
        int $servicioId,
        int $sucursalId,
        int $categoriaTarifaId,
        string $fechaInicio,
        ?int $cantidadSesiones,
        User $actor,
        ?array $diasSemana = null,
        ?string $fechaFin = null,
    ): array {
        if (! $this->access->puedeAcceder($actor->id, $sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        $this->branches->asegurarActiva($sucursalId);
        $this->servicios->assertServicioValido($servicioId);

        $cantidad = CantidadSesionesRule::assertValida(
            $cantidadSesiones ?? $this->settings->cantidadSesionesDefault(),
        );

        $tarifa = $this->tarifas->resolver(
            servicioId: $servicioId,
            sucursalId: $sucursalId,
            categoriaTarifaId: $categoriaTarifaId,
            fecha: $fechaInicio,
        );

        if ($tarifa === null) {
            return [
                'tarifa' => null,
                'tarifa_disponible' => false,
                'cantidad_sesiones' => $cantidad,
                'monto_sesion' => null,
                'monto_total' => null,
                'sesiones_estimadas' => null,
                'advertencia' => null,
                'message' => 'No hay una tarifa vigente para el servicio, sucursal, categoría y fecha seleccionados.',
            ];
        }

        $total = MontoPaqueteRule::calcular($tarifa->monto, $cantidad);
        $estimadas = null;
        $advertencia = null;

        if ($fechaFin !== null && $diasSemana !== null && $diasSemana !== []) {
            $estimadas = PaqueteEstimacionRule::estimar($diasSemana, $fechaInicio, $fechaFin);
            $advertencia = PaqueteEstimacionRule::advertencia($cantidad, $estimadas);
        }

        return [
            'tarifa' => $tarifa->toArray(),
            'tarifa_disponible' => true,
            'cantidad_sesiones' => $cantidad,
            'monto_sesion' => $tarifa->monto,
            'monto_total' => $total,
            'sesiones_estimadas' => $estimadas,
            'advertencia' => $advertencia,
            'message' => null,
        ];
    }
}
