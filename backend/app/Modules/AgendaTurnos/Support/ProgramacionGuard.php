<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Exceptions\AdvertenciasNoConfirmadasException;
use App\Modules\AgendaTurnos\Exceptions\GeneracionBloqueadaException;
use App\Modules\AgendaTurnos\Exceptions\ProfesionalNoAsignableException;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use Illuminate\Support\Facades\DB;

/**
 * Validaciones compartidas por generación, regeneración y asignación.
 */
final class ProgramacionGuard
{
    public function __construct(
        private readonly UserBranchAccessContract $branchAccess,
        private readonly ProfessionalDirectoryContract $profesionales,
    ) {}

    public function asegurarAcceso(User $actor, int $sucursalId): void
    {
        if (! $this->branchAccess->puedeAcceder((int) $actor->id, $sucursalId)) {
            throw new BranchAccessDeniedException;
        }
    }

    public function asegurarProfesional(?int $profesionalId, int $sucursalId): void
    {
        if ($profesionalId === null) {
            return;
        }

        if (! $this->profesionales->existe($profesionalId)) {
            throw new ProfesionalNoAsignableException(
                $profesionalId,
                'El profesional seleccionado no existe.',
            );
        }

        if (! $this->profesionales->estaActivo($profesionalId)) {
            throw new ProfesionalNoAsignableException(
                $profesionalId,
                'El profesional seleccionado está inactivo.',
            );
        }

        if (! array_key_exists($profesionalId, $this->profesionales->nombresPorSucursal($sucursalId))) {
            throw new ProfesionalNoAsignableException(
                $profesionalId,
                'El profesional seleccionado no está asignado a la sucursal de la entidad origen.',
            );
        }
    }

    public function bloquearOrigen(TipoOrigenTurno $tipo, int $origenId): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $clave = hexdec(substr(hash('sha256', $tipo->value.':'.$origenId), 0, 15));

        DB::select('SELECT pg_advisory_xact_lock(?)', [$clave]);
    }

    public function asegurarConfirmable(GeneracionCalculada $calculo): void
    {
        if ($calculo->resumen->puedeConfirmar) {
            return;
        }

        $bloqueos = $this->hallazgosPorSeveridad($calculo, SeveridadHallazgo::BLOQUEO);

        if ($bloqueos !== []) {
            throw new GeneracionBloqueadaException($bloqueos);
        }

        $advertencias = $this->hallazgosPorSeveridad($calculo, SeveridadHallazgo::ADVERTENCIA);

        if ($advertencias !== []) {
            throw new AdvertenciasNoConfirmadasException($advertencias);
        }

        throw new GeneracionBloqueadaException([
            new Hallazgo(
                tipo: 'sin_fechas',
                severidad: SeveridadHallazgo::BLOQUEO,
                mensaje: 'No hay fechas generables en el período de la entidad origen.',
            ),
        ]);
    }

    /**
     * @return list<string>
     */
    public function advertencias(GeneracionCalculada $calculo): array
    {
        return array_values(array_unique(array_map(
            static fn (Hallazgo $hallazgo): string => $hallazgo->mensaje,
            $this->hallazgosPorSeveridad($calculo, SeveridadHallazgo::ADVERTENCIA),
        )));
    }

    /**
     * @return list<Hallazgo>
     */
    public function hallazgosPorSeveridad(
        GeneracionCalculada $calculo,
        SeveridadHallazgo $severidad,
    ): array {
        $resultado = [];

        foreach ($calculo->hallazgos as $hallazgo) {
            if ($hallazgo->severidad === $severidad) {
                $resultado[] = $hallazgo;
            }
        }

        foreach ($calculo->filas as $fila) {
            foreach ($fila->hallazgos as $hallazgo) {
                if ($hallazgo->severidad === $severidad) {
                    $resultado[] = $hallazgo;
                }
            }
        }

        return $this->unicos($resultado);
    }

    /**
     * @param  list<Hallazgo>  $hallazgos
     * @return list<Hallazgo>
     */
    private function unicos(array $hallazgos): array
    {
        $vistos = [];
        $resultado = [];

        foreach ($hallazgos as $hallazgo) {
            $clave = $hallazgo->tipo.'|'.$hallazgo->mensaje.'|'.($hallazgo->turnoId ?? '');

            if (array_key_exists($clave, $vistos)) {
                continue;
            }

            $vistos[$clave] = true;
            $resultado[] = $hallazgo;
        }

        return $resultado;
    }
}
