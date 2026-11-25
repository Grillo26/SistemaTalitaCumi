<?php

namespace Tests\Feature\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Exceptions\PacienteNoAgendableException;
use App\Modules\Pacientes\Exceptions\PacienteNoInscribibleException;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PatientDirectoryContractTest extends PacientesTestCase
{
    public function test_summary_and_existence(): void
    {
        $branch = $this->branch();
        $paciente = $this->paciente([
            'sucursal_registro_id' => $branch->id,
            'fecha_nacimiento' => '2020-05-10',
        ]);

        $contract = app(PatientDirectoryContract::class);

        $this->assertTrue($contract->existe($paciente->id));

        $resumen = $contract->resumen($paciente->id);

        $this->assertSame($paciente->id, $resumen->id);
        $this->assertSame($paciente->nombre_completo, $resumen->nombreCompleto);
        $this->assertSame(EstadoPaciente::ACTIVO, $resumen->estado);
        $this->assertTrue($resumen->sinDiagnosticoDefinido);
        $this->assertSame($branch->id, $resumen->sucursalRegistroId);
    }

    public function test_active_patient_allows_inscription_and_schedule(): void
    {
        $paciente = $this->paciente(['estado' => 'activo']);
        $contract = app(PatientDirectoryContract::class);

        $contract->asegurarPermiteInscripcion($paciente->id);
        $contract->asegurarPermiteTurno($paciente->id);

        $this->assertTrue(true);
    }

    public function test_paused_patient_is_rejected_for_inscription_and_schedule(): void
    {
        $paciente = $this->paciente(['estado' => 'en_pausa']);
        $contract = app(PatientDirectoryContract::class);

        try {
            $contract->asegurarPermiteInscripcion($paciente->id);
            $this->fail('Se esperaba PacienteNoInscribibleException.');
        } catch (PacienteNoInscribibleException $exception) {
            $this->assertSame('PATIENT_PAUSED', $exception->motivoCodigo());
        }

        try {
            $contract->asegurarPermiteTurno($paciente->id);
            $this->fail('Se esperaba PacienteNoAgendableException.');
        } catch (PacienteNoAgendableException $exception) {
            $this->assertSame('PATIENT_PAUSED', $exception->motivoCodigo());
        }
    }

    public function test_retired_patient_is_rejected(): void
    {
        $paciente = $this->paciente(['estado' => 'retirado']);
        $contract = app(PatientDirectoryContract::class);

        $this->expectException(PacienteNoInscribibleException::class);

        $contract->asegurarPermiteInscripcion($paciente->id);
    }

    public function test_missing_patient_throws(): void
    {
        $this->expectException(PacienteNotFoundException::class);

        app(PatientDirectoryContract::class)->resumen(999999);
    }
}
