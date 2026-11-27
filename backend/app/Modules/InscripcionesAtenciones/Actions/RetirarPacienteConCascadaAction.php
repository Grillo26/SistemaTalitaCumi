<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoContract;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoResumen;
use App\Modules\InscripcionesAtenciones\Enums\AccionEstado;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Events\PacienteRetirado;
use App\Modules\InscripcionesAtenciones\Exceptions\AccionEstadoNoPermitidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\EstadoDesactualizadoException;
use App\Modules\InscripcionesAtenciones\Exceptions\FechaBajaInvalidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\RetiroFueraDeSucursalException;
use App\Modules\InscripcionesAtenciones\Rules\EstadoPacienteTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\EstadoInscripcionResolver;
use App\Modules\InscripcionesAtenciones\Support\EstadoMotivoCatalog;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPeriod;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Support\Facades\DB;

/**
 * [Estados Paciente e Inscripción] RF-005 / CU-02 / CA-006.
 *
 * Retira al paciente y arrastra sus inscripciones Activas/En pausa en una
 * sola transacción, con el mismo motivo y fecha. No toca Entrevistas ni
 * Diagnósticos.
 */
final class RetirarPacienteConCascadaAction
{
    public function __construct(
        private readonly PacienteEstadoContract $pacientes,
        private readonly UserBranchAccessContract $access,
        private readonly RetirarInscripcionAction $retirarInscripcion,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{paciente: PacienteEstadoResumen, inscripciones: list<array<string, mixed>>}
     */
    public function __invoke(
        int $pacienteId,
        array $data,
        User $actor,
        ?string $ip = null,
    ): array {
        if (! $actor->can(PacientesPermission::PATIENT_RETIRE)) {
            throw new AccionEstadoNoPermitidaException;
        }

        $fecha = (string) ($data['fecha_baja'] ?? '');
        $hoy = VigenciaPeriod::today();

        if ($fecha !== $hoy) {
            throw new FechaBajaInvalidaException('La fecha de retiro del paciente debe ser hoy.');
        }

        $codigo = isset($data['motivo']) ? (string) $data['motivo'] : null;
        $texto = isset($data['motivo_detalle']) ? trim((string) $data['motivo_detalle']) : null;
        EstadoMotivoCatalog::assertValido(AccionEstado::RETIRAR, null, $codigo, $texto);

        return DB::transaction(function () use (
            $pacienteId,
            $data,
            $actor,
            $ip,
            $codigo,
            $texto,
            $fecha,
            $hoy,
        ): array {
            $actual = $this->pacientes->resumen($pacienteId, $actor->id);
            $esperado = (string) $data['estado_esperado'];

            if ($esperado !== $actual->estado->value) {
                throw new EstadoDesactualizadoException($actual->estado->value, $esperado, [
                    'version_actual' => $actual->version,
                ]);
            }

            EstadoPacienteTransitionRule::assertPermitida($actual->estado, EstadoPaciente::RETIRADO);

            $arrastrables = EstadoInscripcionResolver::arrastrablesDePaciente($pacienteId, $hoy);
            $this->assertExpectedInscriptions($arrastrables, $data['inscripciones'] ?? null);
            $this->assertBranches($arrastrables, $actor);

            $motivo = EstadoMotivoCatalog::compose(
                AccionEstado::RETIRAR,
                null,
                $codigo,
                $texto,
            );

            $paciente = $this->pacientes->cambiarEstado(
                pacienteId: $pacienteId,
                nuevoEstado: EstadoPaciente::RETIRADO,
                motivo: $motivo,
                versionEsperada: $actual->version,
                actorId: $actor->id,
                ip: $ip,
            );

            $retiradas = [];

            foreach ($arrastrables as $arrastrable) {
                $resultado = ($this->retirarInscripcion)(
                    $arrastrable['tipo'],
                    $arrastrable['id'],
                    [
                        'estado_esperado' => $arrastrable['estado_efectivo']->value,
                        'motivo' => $codigo,
                        'motivo_detalle' => $texto,
                        'fecha_baja' => $fecha,
                    ],
                    $actor,
                    $ip,
                );

                $retiradas[] = [
                    'tipo_entidad' => $arrastrable['tipo']->value,
                    'id' => $arrastrable['id'],
                    'sucursal_id' => $arrastrable['sucursal_id'],
                    'estado' => $resultado->estado->value,
                ];
            }

            $sucursales = array_values(array_unique(array_map(
                static fn (array $item): int => $item['sucursal_id'],
                $arrastrables,
            )));

            event(new PacienteRetirado(
                pacienteId: $pacienteId,
                sucursalIds: $sucursales,
                inscripciones: $retiradas,
                motivoCodigo: (string) $codigo,
                motivoTexto: $texto,
                fecha: $fecha,
                actorId: $actor->id,
                ip: $ip,
                from: $actual->estado,
                to: EstadoPaciente::RETIRADO,
            ));

            return [
                'paciente' => $paciente,
                'inscripciones' => $retiradas,
            ];
        });
    }

    /**
     * @param  list<array{tipo: TipoEntidadVigencia, id: int, sucursal_id: int, estado_efectivo: EstadoInscripcion}>  $arrastrables
     */
    private function assertExpectedInscriptions(array $arrastrables, mixed $esperadas): void
    {
        if (! is_array($esperadas)) {
            throw new EstadoDesactualizadoException(
                (string) count($arrastrables),
                'sin confirmación',
                ['inscripciones' => $this->serialize($arrastrables)],
            );
        }

        $actual = [];

        foreach ($arrastrables as $item) {
            $actual[$item['tipo']->value.':'.$item['id']] = $item['estado_efectivo']->value;
        }

        $recibidas = [];

        foreach ($esperadas as $item) {
            if (! is_array($item)) {
                continue;
            }

            $tipo = (string) ($item['tipo_entidad'] ?? '');
            $id = (int) ($item['id'] ?? 0);
            $recibidas[$tipo.':'.$id] = (string) ($item['estado_esperado'] ?? '');
        }

        ksort($actual);
        ksort($recibidas);

        if ($actual !== $recibidas) {
            throw new EstadoDesactualizadoException(
                (string) count($arrastrables),
                (string) count($recibidas),
                ['inscripciones' => $this->serialize($arrastrables)],
            );
        }
    }

    /**
     * @param  list<array{tipo: TipoEntidadVigencia, id: int, sucursal_id: int, estado_efectivo: EstadoInscripcion}>  $arrastrables
     */
    private function assertBranches(array $arrastrables, User $actor): void
    {
        $faltantes = [];

        foreach ($arrastrables as $item) {
            if (! $this->access->puedeAcceder($actor->id, $item['sucursal_id'])) {
                $faltantes[] = $item['sucursal_id'];
            }
        }

        if ($faltantes !== []) {
            throw new RetiroFueraDeSucursalException(array_values(array_unique($faltantes)));
        }
    }

    /**
     * @param  list<array{tipo: TipoEntidadVigencia, id: int, sucursal_id: int, estado_efectivo: EstadoInscripcion}>  $arrastrables
     * @return list<array<string, mixed>>
     */
    private function serialize(array $arrastrables): array
    {
        return array_map(
            static fn (array $item): array => [
                'tipo_entidad' => $item['tipo']->value,
                'id' => $item['id'],
                'estado_esperado' => $item['estado_efectivo']->value,
            ],
            $arrastrables,
        );
    }
}
