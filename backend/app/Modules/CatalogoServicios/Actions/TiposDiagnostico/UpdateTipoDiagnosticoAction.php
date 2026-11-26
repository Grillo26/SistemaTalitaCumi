<?php

namespace App\Modules\CatalogoServicios\Actions\TiposDiagnostico;

use App\Modules\CatalogoServicios\Events\TipoDiagnosticoUpdated;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoEspecialidadInactivaException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoNotFoundException;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Rules\TipoDiagnosticoDiasRule;
use App\Modules\CatalogoServicios\Rules\TipoDiagnosticoEspecialidadesRule;
use App\Modules\CatalogoServicios\Rules\TipoDiagnosticoServicioRule;
use App\Modules\CatalogoServicios\Support\CatalogoDiagnosticosUniquenessGuard;
use App\Modules\CatalogoServicios\Support\TipoDiagnosticoSnapshot;
use App\Modules\Profesionales\Contracts\EspecialidadDirectoryContract;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar tipo de diagnóstico y sus especialidades (RF-D04 / RF-D10).
 */
final class UpdateTipoDiagnosticoAction
{
    public function __construct(
        private readonly CatalogoDiagnosticosUniquenessGuard $uniqueness,
        private readonly EspecialidadDirectoryContract $especialidades,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $tipoDiagnosticoId,
        array $attributes,
        int $version,
        ?int $actorId,
        ?string $ip,
    ): TipoDiagnostico {
        return DB::transaction(function () use (
            $tipoDiagnosticoId,
            $attributes,
            $version,
            $actorId,
            $ip,
        ): TipoDiagnostico {
            $tipo = TipoDiagnostico::query()->lockForUpdate()->find($tipoDiagnosticoId);

            if ($tipo === null) {
                throw new TipoDiagnosticoNotFoundException($tipoDiagnosticoId);
            }

            if ((int) $tipo->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            $before = TipoDiagnosticoSnapshot::from($tipo);

            if (array_key_exists('servicio_id', $attributes)
                && (int) $attributes['servicio_id'] !== $tipo->servicio_id) {
                $servicioId = (int) $attributes['servicio_id'];
                $servicio = Servicio::query()->lockForUpdate()->with('tipoServicio')->find($servicioId);

                if ($servicio === null) {
                    throw new ServicioNotFoundException($servicioId);
                }

                if (! $servicio->activo) {
                    throw new ServicioInactivoException($servicioId);
                }

                TipoDiagnosticoServicioRule::assertEsDiagnostico(
                    $servicio->tipoServicio?->codigo,
                    $servicio->nombre,
                );

                $this->uniqueness->assertServicioAvailable($servicioId, $tipo->id);
                $tipo->servicio_id = $servicioId;
            }

            if (array_key_exists('nombre', $attributes)) {
                $nombre = NombreNormalizadoRule::normalize((string) $attributes['nombre']);
                $this->uniqueness->assertNombreAvailable($nombre, $tipo->id);
                $tipo->nombre = $nombre;
            }

            if (array_key_exists('descripcion', $attributes)) {
                $descripcion = trim((string) $attributes['descripcion']);
                $tipo->descripcion = $descripcion === '' ? null : $descripcion;
            }

            if (array_key_exists('dias_estimados', $attributes)) {
                $tipo->dias_estimados = TipoDiagnosticoDiasRule::normalizeAndValidate(
                    $attributes['dias_estimados'],
                );
            }

            $tipo->version = (int) $tipo->version + 1;

            try {
                $tipo->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            if (array_key_exists('especialidades', $attributes)) {
                $requisitos = TipoDiagnosticoEspecialidadesRule::normalizeAndValidate(
                    is_array($attributes['especialidades']) ? $attributes['especialidades'] : [],
                );
                $this->assertEspecialidadesActivas($requisitos);
                $this->syncEspecialidades($tipo, $requisitos);
            }

            $tipo->load(['servicio.area', 'especialidades']);

            event(new TipoDiagnosticoUpdated(
                tipoDiagnosticoId: $tipo->id,
                before: $before,
                after: TipoDiagnosticoSnapshot::from($tipo),
                actorId: $actorId,
                ip: $ip,
            ));

            return $tipo->refresh()->load(['servicio.area', 'servicio.tipoServicio', 'especialidades']);
        });
    }

    /**
     * @param  list<array{especialidad_id: int, cantidad_minima: int}>  $requisitos
     */
    private function assertEspecialidadesActivas(array $requisitos): void
    {
        foreach ($requisitos as $requisito) {
            $especialidadId = $requisito['especialidad_id'];

            if (! $this->especialidades->existe($especialidadId)
                || ! $this->especialidades->estaActiva($especialidadId)) {
                throw new TipoDiagnosticoEspecialidadInactivaException($especialidadId);
            }
        }
    }

    /**
     * @param  list<array{especialidad_id: int, cantidad_minima: int}>  $requisitos
     */
    private function syncEspecialidades(TipoDiagnostico $tipo, array $requisitos): void
    {
        $sync = [];

        foreach ($requisitos as $requisito) {
            $sync[$requisito['especialidad_id']] = [
                'cantidad_minima' => $requisito['cantidad_minima'],
            ];
        }

        $tipo->especialidades()->sync($sync);
    }
}
