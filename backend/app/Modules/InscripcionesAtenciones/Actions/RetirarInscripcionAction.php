<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\AccionEstado;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Exceptions\AccionEstadoNoPermitidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\EntidadEstadoNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\EstadoDesactualizadoException;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionVencidaNoRetirableException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\EstadoInscripcionTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\EstadoInscripcionResolver;
use App\Modules\InscripcionesAtenciones\Support\EstadoMotivoCatalog;
use App\Modules\InscripcionesAtenciones\Support\EstadoPermission;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;

/**
 * [Estados Paciente e Inscripción] RF-004 / CU-03 / CA-007.
 *
 * Orquesta la baja existente. No duplica sus reglas, eventos ni auditoría.
 * Vencida y Renovada se rechazan antes de delegar: son estados derivados y
 * la spec no las incluye como origen de retiro.
 */
final class RetirarInscripcionAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BajaInscripcionAction $bajaInscripcion,
        private readonly BajaPaqueteAction $bajaPaquete,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(
        TipoEntidadVigencia $tipo,
        int $id,
        array $data,
        User $actor,
        ?string $ip = null,
    ): InscripcionSala|PaqueteSesiones {
        if (! $actor->can(EstadoPermission::withdrawFor($tipo))
            || ! $actor->can(EstadoPermission::viewFor($tipo))) {
            throw new AccionEstadoNoPermitidaException;
        }

        EstadoMotivoCatalog::assertValido(
            accion: AccionEstado::RETIRAR,
            tipo: $tipo,
            codigo: isset($data['motivo']) ? (string) $data['motivo'] : null,
            texto: isset($data['motivo_detalle']) ? (string) $data['motivo_detalle'] : null,
        );

        return DB::transaction(function () use ($tipo, $id, $data, $actor, $ip): InscripcionSala|PaqueteSesiones {
            [$modelo, $efectivo] = $this->load($tipo, $id);

            if (! $this->access->puedeAcceder($actor->id, (int) $modelo->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            $esperado = (string) $data['estado_esperado'];

            if ($esperado !== $efectivo->value) {
                throw new EstadoDesactualizadoException($efectivo->value, $esperado);
            }

            if ($efectivo === EstadoInscripcion::VENCIDA) {
                throw new InscripcionVencidaNoRetirableException;
            }

            EstadoInscripcionTransitionRule::assertPuedeRetirar($efectivo);

            $payload = [
                'motivo' => (string) $data['motivo'],
                'motivo_detalle' => $data['motivo_detalle'] ?? null,
                'fecha_baja' => (string) $data['fecha_baja'],
            ];

            return $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
                ? ($this->bajaInscripcion)($id, $payload, $actor, $ip)
                : ($this->bajaPaquete)($id, $payload, $actor, $ip);
        });
    }

    /**
     * @return array{0: InscripcionSala|PaqueteSesiones, 1: EstadoInscripcion}
     */
    private function load(TipoEntidadVigencia $tipo, int $id): array
    {
        if ($tipo === TipoEntidadVigencia::INSCRIPCION_SALA) {
            $modelo = InscripcionSala::query()->lockForUpdate()->find($id);
        } else {
            $modelo = PaqueteSesiones::query()->lockForUpdate()->find($id);
        }

        if ($modelo === null) {
            throw new EntidadEstadoNotFoundException($tipo, $id);
        }

        $efectivo = $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? EstadoInscripcionResolver::efectivoSala($modelo)
            : EstadoInscripcionResolver::efectivoPaquete($modelo);

        return [$modelo, $efectivo];
    }
}
