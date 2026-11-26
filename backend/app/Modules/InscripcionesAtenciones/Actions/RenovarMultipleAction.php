<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Events\RenovacionMultipleEjecutada;
use App\Modules\InscripcionesAtenciones\Exceptions\RenovacionMultipleInvalidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\VigenciaEntidadNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * [Vigencia y Renovación] RF-016 / CU-02 FA-02.
 *
 * Atómica: si una renovación falla, se revierte el lote completo. Cada
 * entidad conserva su propia confirmación de tarifa.
 */
final class RenovarMultipleAction
{
    public function __construct(private readonly RenovarDesdePanelAction $renovar) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function __invoke(array $data, User $actor, ?string $ip = null): array
    {
        $items = $data['items'] ?? null;

        if (! is_array($items) || $items === []) {
            throw new RenovacionMultipleInvalidaException('Indicá al menos una entidad para renovar.');
        }

        $pacienteId = null;
        $sucursalIds = [];
        $claves = [];

        foreach ($items as $item) {
            $tipo = TipoEntidadVigencia::from((string) $item['tipo_entidad']);
            $entidadId = (int) $item['entidad_id'];
            $clave = $tipo->value.':'.$entidadId;

            if (isset($claves[$clave])) {
                throw new RenovacionMultipleInvalidaException(
                    'No se puede renovar dos veces la misma entidad en un lote.',
                );
            }

            $claves[$clave] = true;
            $origen = $this->origen($tipo, $entidadId);

            if ($pacienteId === null) {
                $pacienteId = (int) $origen->paciente_id;
            } elseif ($pacienteId !== (int) $origen->paciente_id) {
                throw new RenovacionMultipleInvalidaException(
                    'Todas las entidades de un lote deben pertenecer al mismo paciente.',
                );
            }

            $sucursalIds[] = (int) $origen->sucursal_id;
        }

        $loteId = (string) Str::uuid();

        return DB::transaction(function () use (
            $items,
            $actor,
            $ip,
            $loteId,
            $pacienteId,
            $sucursalIds,
        ): array {
            $resultados = [];

            foreach ($items as $item) {
                $tipo = TipoEntidadVigencia::from((string) $item['tipo_entidad']);
                $entidadId = (int) $item['entidad_id'];
                $payload = $item;
                unset($payload['tipo_entidad'], $payload['entidad_id']);

                $resultado = ($this->renovar)($tipo, $entidadId, $payload, $actor, $ip);
                $resultados[] = $resultado->toArray() + [
                    'sucursal_id' => (int) $this->origen($tipo, $entidadId)->sucursal_id,
                ];
            }

            event(new RenovacionMultipleEjecutada(
                loteId: $loteId,
                pacienteId: (int) $pacienteId,
                sucursalIds: array_values(array_unique($sucursalIds)),
                items: $resultados,
                actorId: $actor->id,
                ip: $ip,
            ));

            return [
                'lote_id' => $loteId,
                'paciente_id' => (int) $pacienteId,
                'items' => $resultados,
            ];
        });
    }

    private function origen(TipoEntidadVigencia $tipo, int $id): InscripcionSala|PaqueteSesiones
    {
        $origen = $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? InscripcionSala::query()->find($id)
            : PaqueteSesiones::query()->find($id);

        if ($origen === null) {
            throw new VigenciaEntidadNotFoundException($tipo, $id);
        }

        return $origen;
    }
}
