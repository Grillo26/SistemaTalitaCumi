<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Models\User;
use App\Modules\AgendaTurnos\Exceptions\CancelacionMasivaFueraDeSucursalException;
use App\Modules\AgendaTurnos\Exceptions\MotivoObligatorioException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\CancelacionMasivaRule;
use App\Modules\AgendaTurnos\Rules\MotivoObligatorioRule;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;

/**
 * Cálculo compartido por el preview y la confirmación de cancelación masiva.
 */
final class CancelacionMasivaCalculator
{
    public function __construct(
        private readonly MotivoTurnoResolver $motivos,
        private readonly MotivoObligatorioRule $motivoRule,
        private readonly CancelacionMasivaRule $regla,
        private readonly UserBranchAccessContract $access,
        private readonly AgendaClock $clock,
    ) {}

    /**
     * @param  list<array{tipo: string, id: int}>  $entidades
     * @return array<string, mixed>
     */
    public function calcular(
        int $pacienteId,
        array $entidades,
        ?int $servicioId,
        int $motivoId,
        ?string $motivoTexto,
        User $actor,
    ): array {
        $motivo = $this->motivos->obtener($motivoId);
        $errores = $this->motivoRule->errores($motivo, 'cancelar', $motivoTexto);

        if ($errores !== [] || $motivo === null) {
            throw new MotivoObligatorioException($errores);
        }

        $turnos = $this->turnos($pacienteId, $entidades, $servicioId);
        $sucursales = array_values(array_unique(array_map(
            static fn (Turno $turno): int => (int) $turno->sucursal_id,
            $turnos,
        )));
        $accesibles = $this->access->accesibles((int) $actor->id);
        $faltantes = array_values(array_diff($sucursales, $accesibles));

        if ($faltantes !== []) {
            throw new CancelacionMasivaFueraDeSucursalException($faltantes);
        }

        $existentes = array_map(
            static fn (Turno $turno): TurnoExistente => TurnoExistente::fromTurno($turno),
            $turnos,
        );

        $clasificacion = $this->regla->evaluar(
            $existentes,
            $this->clock->hoy(),
            $this->clock->horaActual(),
        );

        return [
            'clasificacion' => $clasificacion,
            'motivo' => $motivo,
            'sucursales' => $sucursales,
            'entidades' => $entidades,
            'elegibles' => array_map(
                static fn (TurnoExistente $turno): array => $turno->toArray(),
                $clasificacion->elegibles,
            ),
            'excluidos' => array_map(
                static fn (TurnoExcluido $turno): array => $turno->toArray(),
                $clasificacion->excluidos,
            ),
            'cantidades' => [
                'elegibles' => $clasificacion->cantidadElegibles(),
                'excluidos' => $clasificacion->cantidadExcluidos(),
            ],
        ];
    }

    /**
     * @param  list<array{tipo: string, id: int}>  $entidades
     * @return list<Turno>
     */
    private function turnos(int $pacienteId, array $entidades, ?int $servicioId): array
    {
        if ($entidades === []) {
            return [];
        }

        $query = Turno::query()
            ->where('paciente_id', $pacienteId)
            ->where(static function ($sub) use ($entidades): void {
                foreach ($entidades as $entidad) {
                    $columna = ($entidad['tipo'] ?? '') === 'inscripcion_sala'
                        ? 'inscripcion_sala_id'
                        : 'paquete_sesiones_id';

                    $sub->orWhere($columna, (int) ($entidad['id'] ?? 0));
                }
            })
            ->ordenado();

        if ($servicioId !== null) {
            $query->where('servicio_id', $servicioId);
        }

        return $query->get()->all();
    }
}
