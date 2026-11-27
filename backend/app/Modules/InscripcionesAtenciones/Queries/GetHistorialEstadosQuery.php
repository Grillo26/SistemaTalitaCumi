<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoContract;
use App\Modules\InscripcionesAtenciones\Enums\AccionEstado;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Exceptions\AccionEstadoNoPermitidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\EntidadEstadoNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Support\EstadoMotivoCatalog;
use App\Modules\InscripcionesAtenciones\Support\EstadoPermission;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Seguridad\Models\BitacoraEntry;

/**
 * [Estados Paciente e Inscripción] RF-010 / RN-011 / RNF-011 / CA-010.
 *
 * Historial desde bitácora. Se exponen solo datos de transición, no los
 * snapshots completos ni datos clínicos.
 */
final class GetHistorialEstadosQuery
{
    public function __construct(
        private readonly PacienteEstadoContract $pacientes,
        private readonly UserBranchAccessContract $access,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function paciente(int $pacienteId, User $actor): array
    {
        $this->pacientes->resumen($pacienteId, $actor->id);

        $entries = BitacoraEntry::query()
            ->with('usuario:id,name')
            ->where('entidad', 'paciente')
            ->where('entidad_id', $pacienteId)
            ->whereIn('accion', ['CREAR', 'CAMBIAR_ESTADO'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $entries
            ->map(fn (BitacoraEntry $entry): array => $this->mapPatient($entry))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function inscripcion(TipoEntidadVigencia $tipo, int $id, User $actor): array
    {
        if (! $actor->can(EstadoPermission::VIEW)
            || ! $actor->can(EstadoPermission::viewFor($tipo))) {
            throw new AccionEstadoNoPermitidaException;
        }

        $this->assertInscriptionAccess($tipo, $id, $actor);

        $entries = BitacoraEntry::query()
            ->with('usuario:id,name')
            ->where('entidad', $tipo->value)
            ->where('entidad_id', $id)
            ->whereIn('accion', ['CREAR', 'RETIRAR', 'RENOVAR', 'PAUSAR', 'REACTIVAR', 'ACTUALIZAR'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $entries
            ->filter(fn (BitacoraEntry $entry): bool => $this->cambiaEstado($entry))
            ->map(fn (BitacoraEntry $entry): array => $this->mapInscription($entry, $tipo))
            ->values()
            ->all();
    }

    private function cambiaEstado(BitacoraEntry $entry): bool
    {
        if ($entry->accion !== 'ACTUALIZAR') {
            return true;
        }

        $before = $entry->datos_anteriores['estado'] ?? null;
        $after = $entry->datos_nuevos['estado'] ?? null;

        return $before !== $after;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPatient(BitacoraEntry $entry): array
    {
        $before = $entry->datos_anteriores ?? [];
        $after = $entry->datos_nuevos ?? [];
        $anterior = $before['estado'] ?? null;
        $nuevo = $after['estado'] ?? null;
        $from = $anterior === null ? null : EstadoPaciente::tryFrom((string) $anterior);
        $to = $nuevo === null ? null : EstadoPaciente::tryFrom((string) $nuevo);

        return [
            'accion' => $entry->accion,
            'estado_anterior' => $from?->value,
            'estado_anterior_label' => $from?->label(),
            'estado_nuevo' => $to?->value,
            'estado_nuevo_label' => $to?->label(),
            'motivo_codigo' => null,
            'motivo_texto' => $entry->motivo,
            'motivo_label' => $entry->motivo,
            'fecha' => $entry->created_at?->toIso8601String(),
            'usuario' => $entry->usuario?->name,
            'critico' => (bool) $entry->critico,
            'origen' => $entry->accion === 'CREAR' ? 'sistema' : 'manual',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapInscription(BitacoraEntry $entry, TipoEntidadVigencia $tipo): array
    {
        $before = $entry->datos_anteriores ?? [];
        $after = $entry->datos_nuevos ?? [];
        $anteriorRaw = $before['estado'] ?? null;
        $nuevoRaw = $after['estado'] ?? null;
        $anterior = $anteriorRaw === null ? null : $this->label($tipo, (string) $anteriorRaw);
        $nuevo = $nuevoRaw === null ? null : $this->label($tipo, (string) $nuevoRaw);
        $codigo = $after['motivo_codigo'] ?? null;
        $detalle = $after['motivo_detalle'] ?? null;
        $accion = match ($entry->accion) {
            'PAUSAR' => AccionEstado::PAUSAR,
            'RETIRAR' => AccionEstado::RETIRAR,
            default => null,
        };
        $motivoLabel = $accion === null
            ? null
            : EstadoMotivoCatalog::label($accion, $tipo, is_string($codigo) ? $codigo : null);
        $fecha = $after['fecha_baja'] ?? null;

        return [
            'accion' => $entry->accion,
            'estado_anterior' => $anterior?->value,
            'estado_anterior_label' => $anterior?->label(),
            'estado_nuevo' => $nuevo?->value,
            'estado_nuevo_label' => $nuevo?->label(),
            'motivo_codigo' => $codigo,
            'motivo_texto' => is_string($detalle) && $detalle !== '' ? $detalle : $entry->motivo,
            'motivo_label' => $motivoLabel ?? $entry->motivo,
            'fecha' => is_string($fecha) && $fecha !== ''
                ? $fecha.'T00:00:00-04:00'
                : $entry->created_at?->toIso8601String(),
            'usuario' => $entry->usuario?->name,
            'critico' => (bool) $entry->critico,
            'origen' => match ($entry->accion) {
                'CREAR' => 'sistema',
                'RENOVAR' => 'sistema',
                default => 'manual',
            },
        ];
    }

    private function label(TipoEntidadVigencia $tipo, string $estado): EstadoInscripcion
    {
        return EstadoInscripcion::fromStored($tipo->value, $estado);
    }

    private function assertInscriptionAccess(TipoEntidadVigencia $tipo, int $id, User $actor): void
    {
        if ($tipo === TipoEntidadVigencia::INSCRIPCION_SALA) {
            $modelo = InscripcionSala::query()->find($id);
        } else {
            $modelo = PaqueteSesiones::query()->find($id);
        }

        if ($modelo === null) {
            throw new EntidadEstadoNotFoundException($tipo, $id);
        }

        if (! $this->access->puedeAcceder($actor->id, (int) $modelo->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }
    }
}
