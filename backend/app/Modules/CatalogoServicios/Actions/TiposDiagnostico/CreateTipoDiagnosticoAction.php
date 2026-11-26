<?php

namespace App\Modules\CatalogoServicios\Actions\TiposDiagnostico;

use App\Modules\CatalogoServicios\Events\TipoDiagnosticoCreated;
use App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoEspecialidadInactivaException;
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
 * Crear tipo de diagnóstico (RF-D01 a RF-D04 / RN-D08).
 */
final class CreateTipoDiagnosticoAction
{
    public function __construct(
        private readonly CatalogoDiagnosticosUniquenessGuard $uniqueness,
        private readonly EspecialidadDirectoryContract $especialidades,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId, ?string $ip): TipoDiagnostico
    {
        return DB::transaction(function () use ($attributes, $actorId, $ip): TipoDiagnostico {
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

            $nombre = NombreNormalizadoRule::normalize((string) $attributes['nombre']);
            $this->uniqueness->assertNombreAvailable($nombre);
            $this->uniqueness->assertServicioAvailable($servicioId);

            $requisitos = TipoDiagnosticoEspecialidadesRule::normalizeAndValidate(
                is_array($attributes['especialidades'] ?? null) ? $attributes['especialidades'] : [],
            );
            $this->assertEspecialidadesActivas($requisitos);

            $dias = TipoDiagnosticoDiasRule::normalizeAndValidate($attributes['dias_estimados'] ?? 1);

            try {
                $tipo = TipoDiagnostico::query()->create([
                    'servicio_id' => $servicioId,
                    'nombre' => $nombre,
                    'descripcion' => $this->nullableString($attributes['descripcion'] ?? null),
                    'dias_estimados' => $dias,
                    'activo' => (bool) ($attributes['activo'] ?? true),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $this->syncEspecialidades($tipo, $requisitos);
            $tipo->load(['servicio', 'especialidades']);

            event(new TipoDiagnosticoCreated(
                tipoDiagnosticoId: $tipo->id,
                snapshot: TipoDiagnosticoSnapshot::from($tipo),
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

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
