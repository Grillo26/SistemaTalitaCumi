<?php

namespace Tests\Feature\Modules\Pacientes;

use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PatientDirectoryBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_names_in_batch_including_inactive_patients(): void
    {
        $active = Paciente::factory()->create(['estado' => 'activo']);
        $retired = Paciente::factory()->create(['estado' => 'retirado']);
        $directory = app(PatientDirectoryContract::class);

        $names = $directory->nombres([$active->id, $retired->id, 999999]);

        $this->assertSame($active->nombre_completo, $names[$active->id]);
        $this->assertSame($retired->nombre_completo, $names[$retired->id]);
        $this->assertArrayNotHasKey(999999, $names);
        $this->assertSame([], $directory->nombres([]));
    }
}
