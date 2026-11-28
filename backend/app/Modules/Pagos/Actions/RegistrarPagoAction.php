<?php

namespace App\Modules\Pagos\Actions;

use App\Models\User;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorResumen;
use App\Modules\Pagos\Contracts\PayableEntityContract;
use App\Modules\Pagos\Contracts\PayableEntityResumen;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\TipoEntidadPago;
use App\Modules\Pagos\Events\PagoRegistrado;
use App\Modules\Pagos\Exceptions\PagoConfiguracionIncompletaException;
use App\Modules\Pagos\Exceptions\PagoEntidadNoEncontradaException;
use App\Modules\Pagos\Exceptions\PagoEntidadNoPagableException;
use App\Modules\Pagos\Exceptions\PagoIdempotenciaConflictoException;
use App\Modules\Pagos\Exceptions\PagoModuloNoDisponibleException;
use App\Modules\Pagos\Exceptions\PagoTutorInvalidoException;
use App\Modules\Pagos\Models\MedioPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\PagoIdempotencia;
use App\Modules\Pagos\Models\SecuenciaPago;
use App\Modules\Pagos\Rules\FechaPagoRule;
use App\Modules\Pagos\Rules\MontoPagoRule;
use App\Modules\Pagos\Rules\PagoIdempotenciaRule;
use App\Modules\Pagos\Rules\PagoLineasRule;
use App\Modules\Pagos\Rules\SaldoPagoRule;
use App\Modules\Pagos\Support\PagoNumberFormatter;
use App\Modules\Pagos\Support\PagoPermission;
use App\Modules\Pagos\Support\PagosModule;
use App\Modules\Pagos\Support\PagoSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * [08.01 Registro de pagos y saldos] RF-001, RF-002, RF-003, RF-004, RF-005,
 * RF-006, RF-010, RF-014, RF-015, RF-016, RN-004, RN-005, RN-007, RN-009,
 * RN-010, RN-011, CU-01 (FA-01), CU-02, RT-001, RT-003.
 *
 * Orden: validar entidad y alcance -> reglas de línea y fecha -> idempotencia
 * -> transacción con bloqueo del correlativo de la sucursal, saldo, inserción
 * y bitácora (síncrona y fail-closed). Un pago nunca se crea sin la bitácora.
 */
final class RegistrarPagoAction
{
    private const ESTADOS_NO_PAGABLES = ['retirada', 'retirado', 'anulada', 'anulado'];

    public function __construct(
        private readonly PayableEntityContract $entidades,
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly TutorDirectoryContract $tutores,
        private readonly ParameterReaderContract $parametros,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data, User $actor, ?string $ip = null): RegistroPagoResultado
    {
        $tipo = TipoEntidadPago::from((string) $data['tipo_entidad']);

        if (! $tipo->disponibleEnEtapa()) {
            throw new PagoModuloNoDisponibleException(
                'Venta de materiales (módulo 10.3)',
                'El pago de ventas de materiales se habilitará con el módulo 10.3.',
            );
        }

        $entidadId = (int) $data['entidad_id'];
        $clave = (string) $data['clave_idempotencia'];

        $config = $this->configuracion();

        $entidad = $this->entidades->resumen($tipo, $entidadId);

        if (! $this->access->puedeAcceder($actor->id, $entidad->sucursalId)) {
            throw new PagoEntidadNoEncontradaException;
        }

        $this->branches->asegurarActiva($entidad->sucursalId);

        if (in_array($entidad->estado, self::ESTADOS_NO_PAGABLES, true)) {
            throw new PagoEntidadNoPagableException;
        }

        $hoy = CarbonImmutable::now($config['zona'])->toDateString();
        $fecha = isset($data['fecha_pago']) && $data['fecha_pago'] !== ''
            ? (string) $data['fecha_pago']
            : $hoy;
        $motivoFuturo = isset($data['motivo_fecha_futura']) ? trim((string) $data['motivo_fecha_futura']) : null;
        $motivoFuturo = $motivoFuturo === '' ? null : $motivoFuturo;

        $fechaFutura = FechaPagoRule::assertValida(
            $fecha,
            $hoy,
            $actor->can(PagoPermission::AUTHORIZE_FUTURE),
            $motivoFuturo,
        );

        $lineas = $this->normalizarLineas($data['lineas'] ?? []);
        PagoLineasRule::assertValidas($lineas, MedioPago::query()->activos()->pluck('codigo')->all());

        $tutor = $this->resolverTutor($entidad->pacienteId, $data['tutor_id'] ?? null);

        $total = $this->sumar(array_column($lineas, 'monto'));
        $huella = PagoIdempotenciaRule::huella([
            'tipo' => $tipo->value,
            'entidad_id' => $entidadId,
            'fecha_pago' => $fecha,
            'tutor_id' => $tutor->id,
            'motivo_fecha_futura' => $motivoFuturo,
            'lineas' => array_map(static fn (array $linea): array => [
                'medio_pago' => $linea['medio_pago'],
                'monto' => $linea['monto'],
                'referencia' => $linea['referencia'],
            ], $lineas),
        ]);

        $previo = $this->buscarIdempotencia($actor->id, $entidad->sucursalId, $clave, $huella);

        if ($previo !== null) {
            return $previo;
        }

        return DB::transaction(function () use (
            $tipo,
            $entidad,
            $entidadId,
            $fecha,
            $fechaFutura,
            $motivoFuturo,
            $lineas,
            $tutor,
            $total,
            $huella,
            $clave,
            $config,
            $actor,
            $ip,
        ): RegistroPagoResultado {
            SecuenciaPago::query()->insertOrIgnore([
                'sucursal_id' => $entidad->sucursalId,
                'ultimo_numero' => 0,
            ]);

            $secuencia = SecuenciaPago::query()
                ->whereKey($entidad->sucursalId)
                ->lockForUpdate()
                ->firstOrFail();

            $previo = $this->buscarIdempotencia($actor->id, $entidad->sucursalId, $clave, $huella);

            if ($previo !== null) {
                return $previo;
            }

            $pagado = $this->pagadoVigente($tipo, $entidadId);
            $montoEsperado = bcadd($entidad->montoEsperado, '0', 2);
            $saldo = SaldoPagoRule::saldo($montoEsperado, $pagado);
            SaldoPagoRule::assertCubre($total, $saldo);
            $saldoResultante = SaldoPagoRule::resultante($saldo, $total);

            $grupoId = (string) Str::uuid();
            $creados = collect();
            $numero = (int) $secuencia->ultimo_numero;

            foreach ($lineas as $linea) {
                $numero++;

                $pago = Pago::query()->create([
                    'numero_correlativo' => PagoNumberFormatter::formatear(
                        $config['prefijo'],
                        $config['formato'],
                        $numero,
                    ),
                    'sucursal_id' => $entidad->sucursalId,
                    'sucursal_codigo_snapshot' => $entidad->sucursalCodigo,
                    'sucursal_nombre_snapshot' => $entidad->sucursalNombre,
                    'paciente_id' => $entidad->pacienteId,
                    'paciente_nombre_snapshot' => $entidad->pacienteNombre,
                    'tutor_id' => $tutor->id,
                    'tutor_nombre_snapshot' => $tutor->nombreCompleto,
                    ...$this->vinculoEntidad($tipo, $entidadId),
                    'entidad_referencia_snapshot' => $entidad->referencia,
                    'monto_esperado_snapshot' => $montoEsperado,
                    'monto' => $linea['monto'],
                    'saldo_resultante' => $saldoResultante,
                    'medio_pago' => $linea['medio_pago'],
                    'referencia_pago' => $linea['referencia'],
                    'grupo_pago_id' => $grupoId,
                    'fecha_pago' => $fecha,
                    'fecha_futura_autorizada' => $fechaFutura,
                    'motivo_fecha_futura' => $fechaFutura ? $motivoFuturo : null,
                    'autorizado_por_id' => $fechaFutura ? $actor->id : null,
                    'estado' => EstadoPago::VIGENTE->value,
                    'usuario_registro_id' => $actor->id,
                ]);

                $creados->push($pago);
            }

            $secuencia->ultimo_numero = $numero;
            $secuencia->save();

            PagoIdempotencia::query()->create([
                'usuario_id' => $actor->id,
                'sucursal_id' => $entidad->sucursalId,
                'clave' => $clave,
                'hash_solicitud' => $huella,
                'grupo_pago_id' => $grupoId,
            ]);

            event(new PagoRegistrado(
                grupoPagoId: $grupoId,
                pagos: $creados
                    ->map(static fn (Pago $pago): array => PagoSnapshot::from($pago))
                    ->values()
                    ->all(),
                fechaFutura: $fechaFutura,
                actorId: (int) $actor->id,
                ip: $ip,
            ));

            return new RegistroPagoResultado(
                grupoPagoId: $grupoId,
                pagos: $creados->map(static fn (Pago $pago): Pago => $pago->refresh()),
                entidad: $entidad,
                saldoResultante: $saldoResultante,
                replay: false,
            );
        });
    }

    /**
     * @return array<string, string>
     *
     * @throws PagoConfiguracionIncompletaException
     */
    private function configuracion(): array
    {
        try {
            $valores = $this->parametros->obtenerVarios([
                PagosModule::CONFIG_ZONA_HORARIA,
                PagosModule::CONFIG_MONEDA,
                PagosModule::LEYENDA_CONFIG_PREFIJO,
                PagosModule::LEYENDA_CONFIG_FORMATO,
            ]);
        } catch (ParameterNotFoundException) {
            throw new PagoConfiguracionIncompletaException;
        }

        return [
            'zona' => (string) $valores[PagosModule::CONFIG_ZONA_HORARIA]->valor,
            'prefijo' => (string) $valores[PagosModule::LEYENDA_CONFIG_PREFIJO]->valor,
            'formato' => (string) $valores[PagosModule::LEYENDA_CONFIG_FORMATO]->valor,
        ];
    }

    /**
     * @return list<array{medio_pago: string, monto: string, referencia: ?string}>
     */
    private function normalizarLineas(mixed $lineas): array
    {
        if (! is_array($lineas)) {
            return [];
        }

        return array_values(array_map(static function (mixed $linea): array {
            $linea = is_array($linea) ? $linea : [];
            $referencia = isset($linea['referencia']) ? trim((string) $linea['referencia']) : null;

            return [
                'medio_pago' => (string) ($linea['medio_pago'] ?? ''),
                'monto' => MontoPagoRule::normalizar($linea['monto'] ?? null),
                'referencia' => $referencia === '' ? null : $referencia,
            ];
        }, $lineas));
    }

    /**
     * Tutor (08.01/RF-001): el solicitado debe ser activo y del paciente; si no
     * viene, se usa el tutor principal activo.
     */
    private function resolverTutor(int $pacienteId, mixed $tutorIdSolicitado): TutorResumen
    {
        if ($tutorIdSolicitado !== null && $tutorIdSolicitado !== '') {
            foreach ($this->tutores->tutoresDePaciente($pacienteId) as $tutor) {
                if ($tutor->id === (int) $tutorIdSolicitado && $tutor->activo) {
                    return $tutor;
                }
            }

            throw new PagoTutorInvalidoException;
        }

        $principal = $this->tutores->tutorPrincipal($pacienteId);

        if ($principal === null) {
            throw new PagoTutorInvalidoException(
                'El paciente no tiene un tutor principal activo. Indicá el tutor responsable del pago.',
            );
        }

        return $principal;
    }

    /**
     * @param  list<string>  $montos
     */
    private function sumar(array $montos): string
    {
        $total = '0.00';

        foreach ($montos as $monto) {
            $total = bcadd($total, $monto, 2);
        }

        return $total;
    }

    private function pagadoVigente(TipoEntidadPago $tipo, int $entidadId): string
    {
        $montos = Pago::query()
            ->vigentes()
            ->porEntidad($tipo, $entidadId)
            ->pluck('monto')
            ->all();

        return $this->sumar(array_map(static fn (mixed $monto): string => (string) $monto, $montos));
    }

    /**
     * @return array<string, int>
     */
    private function vinculoEntidad(TipoEntidadPago $tipo, int $entidadId): array
    {
        $columna = $tipo->columna();

        return $columna === null ? [] : [$columna => $entidadId];
    }

    /**
     * Reintento idempotente: misma clave y contenido devuelve el grupo original;
     * distinto contenido responde 409.
     */
    private function buscarIdempotencia(
        int $usuarioId,
        int $sucursalId,
        string $clave,
        string $huella,
    ): ?RegistroPagoResultado {
        $registro = PagoIdempotencia::query()
            ->where('usuario_id', $usuarioId)
            ->where('sucursal_id', $sucursalId)
            ->where('clave', $clave)
            ->first();

        if ($registro === null) {
            return null;
        }

        if (! hash_equals($registro->hash_solicitud, $huella)) {
            throw new PagoIdempotenciaConflictoException;
        }

        $pagos = Pago::query()
            ->where('grupo_pago_id', $registro->grupo_pago_id)
            ->orderBy('id')
            ->get();

        $primero = $pagos->first();

        return new RegistroPagoResultado(
            grupoPagoId: (string) $registro->grupo_pago_id,
            pagos: $pagos,
            entidad: $this->entidadDesdePago($primero),
            saldoResultante: (string) $primero->saldo_resultante,
            replay: true,
        );
    }

    private function entidadDesdePago(Pago $pago): PayableEntityResumen
    {
        $tipo = TipoEntidadPago::INSCRIPCION_SALA;

        foreach (TipoEntidadPago::cases() as $candidato) {
            $columna = $candidato->columna();

            if ($columna !== null && $pago->{$columna} !== null) {
                $tipo = $candidato;
                break;
            }
        }

        return new PayableEntityResumen(
            tipo: $tipo,
            id: (int) $pago->{$tipo->columna()},
            sucursalId: (int) $pago->sucursal_id,
            sucursalCodigo: (string) $pago->sucursal_codigo_snapshot,
            sucursalNombre: (string) $pago->sucursal_nombre_snapshot,
            pacienteId: (int) $pago->paciente_id,
            pacienteNombre: (string) $pago->paciente_nombre_snapshot,
            referencia: (string) $pago->entidad_referencia_snapshot,
            estado: '',
            montoEsperado: (string) $pago->monto_esperado_snapshot,
            tutorSugeridoId: (int) $pago->tutor_id,
            tutorSugeridoNombre: (string) $pago->tutor_nombre_snapshot,
        );
    }
}
