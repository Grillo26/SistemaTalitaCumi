<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResolverContract;
use App\Modules\InscripcionesAtenciones\Enums\EstadoAtencion;
use App\Modules\InscripcionesAtenciones\Events\EntrevistaCreated;
use App\Modules\InscripcionesAtenciones\Exceptions\TarifaCambioException;
use App\Modules\InscripcionesAtenciones\Exceptions\TutorNoPerteneceAlPacienteException;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\InscripcionesAtenciones\Rules\AtencionDatosObligatoriosRule;
use App\Modules\InscripcionesAtenciones\Rules\AtencionFechasRule;
use App\Modules\InscripcionesAtenciones\Rules\NotasAdministrativasRule;
use App\Modules\InscripcionesAtenciones\Rules\TarifaVigenteRule;
use App\Modules\InscripcionesAtenciones\Support\AtencionSnapshot;
use App\Modules\InscripcionesAtenciones\Support\EntrevistaServicioGuard;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Exceptions\TutorInactiveException;
use Illuminate\Support\Facades\DB;

/**
 * [Entrevista y Diagnóstico] RF-001, RF-006, RF-007, RF-010, RF-015.
 * RN-001, RN-007, RN-009, RN-011. CA-001.
 */
final class CreateEntrevistaAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly PatientDirectoryContract $patients,
        private readonly TutorDirectoryContract $tutors,
        private readonly TarifaAplicableResolverContract $tarifas,
        private readonly EntrevistaServicioGuard $servicios,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data, User $actor, ?string $ip = null): AtencionEntrevista
    {
        $pacienteId = (int) $data['paciente_id'];
        $tutorId = (int) $data['tutor_id'];
        $sucursalId = (int) $data['sucursal_id'];
        $servicioId = (int) $data['servicio_id'];
        $categoriaId = (int) $data['categoria_tarifa_id'];
        $fecha = (string) $data['fecha'];
        $notas = $this->nullableString($data['notas_admin'] ?? null);

        if (! $this->access->puedeAcceder($actor->id, $sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        $this->branches->asegurarActiva($sucursalId);
        $this->patients->asegurarPermiteInscripcion($pacienteId);

        $tutores = $this->tutors->tutoresDePaciente($pacienteId);
        $tutor = null;

        foreach ($tutores as $candidate) {
            if ($candidate->id === $tutorId) {
                $tutor = $candidate;
                break;
            }
        }

        if ($tutor === null) {
            throw new TutorNoPerteneceAlPacienteException($tutorId, $pacienteId);
        }

        if (! $tutor->activo) {
            throw new TutorInactiveException;
        }

        $this->servicios->assertServicioValido($servicioId);

        $tarifa = $this->tarifas->resolver($servicioId, $sucursalId, $categoriaId, $fecha);
        TarifaVigenteRule::assertVigente($tarifa, $fecha);

        AtencionFechasRule::assertEntrevista($fecha);
        NotasAdministrativasRule::assertValidas($notas);
        AtencionDatosObligatoriosRule::assertCompletos([
            'paciente_id' => $pacienteId,
            'tutor_id' => $tutorId,
            'sucursal_id' => $sucursalId,
            'servicio_id' => $servicioId,
            'categoria_tarifa_id' => $categoriaId,
            'fecha' => $fecha,
        ]);

        $tarifaEsperada = isset($data['tarifa_id_esperada']) ? (int) $data['tarifa_id_esperada'] : null;
        $confirmar = (bool) ($data['confirmar_tarifa_cambio'] ?? false);

        if ($tarifaEsperada !== null && $tarifaEsperada !== $tarifa->tarifaId && ! $confirmar) {
            throw new TarifaCambioException($tarifa->toArray());
        }

        return DB::transaction(function () use (
            $pacienteId,
            $tutorId,
            $sucursalId,
            $servicioId,
            $categoriaId,
            $fecha,
            $notas,
            $tarifa,
            $actor,
            $ip,
        ): AtencionEntrevista {
            $entrevista = AtencionEntrevista::query()->create([
                'paciente_id' => $pacienteId,
                'tutor_id' => $tutorId,
                'sucursal_id' => $sucursalId,
                'servicio_id' => $servicioId,
                'tarifa_id' => $tarifa->tarifaId,
                'categoria_tarifa_id' => $categoriaId,
                'fecha' => $fecha,
                'monto_esperado' => $tarifa->monto,
                'estado' => EstadoAtencion::ACTIVA->value,
                'notas_admin' => $notas,
            ]);

            $entrevista->refresh();
            $snapshot = AtencionSnapshot::entrevista($entrevista);

            event(new EntrevistaCreated(
                entrevistaId: (int) $entrevista->id,
                pacienteId: $pacienteId,
                tutorId: $tutorId,
                sucursalId: $sucursalId,
                servicioId: $servicioId,
                tarifaId: $tarifa->tarifaId,
                categoriaTarifaId: $categoriaId,
                fecha: $fecha,
                montoEsperado: $tarifa->monto,
                actorId: $actor->id,
                ip: $ip,
                snapshot: $snapshot,
            ));

            return $entrevista;
        });
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
