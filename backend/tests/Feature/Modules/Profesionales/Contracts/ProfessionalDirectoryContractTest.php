<?php

namespace Tests\Feature\Modules\Profesionales\Contracts;

use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use App\Modules\Profesionales\Exceptions\ProfesionalInactivoException;
use App\Modules\Profesionales\Exceptions\ProfesionalNotFoundException;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class ProfessionalDirectoryContractTest extends ProfesionalesTestCase
{
    public function test_directory_reports_existence_and_active_state(): void
    {
        $contract = app(ProfessionalDirectoryContract::class);
        $profesional = $this->profesional();

        $this->assertTrue($contract->existe($profesional->id));
        $this->assertTrue($contract->estaActivo($profesional->id));
        $this->assertFalse($contract->existe(999999));

        $profesional->forceFill(['activo' => false])->save();

        $this->assertTrue($contract->existe($profesional->id));
        $this->assertFalse($contract->estaActivo($profesional->id));
    }

    public function test_directory_throws_for_missing_or_inactive_professional(): void
    {
        $contract = app(ProfessionalDirectoryContract::class);
        $profesional = $this->profesional();

        $contract->asegurarActivo($profesional->id);

        try {
            $contract->asegurarActivo(999999);
            $this->fail('Se esperaba ProfesionalNotFoundException.');
        } catch (ProfesionalNotFoundException) {
            $this->assertTrue(true);
        }

        $profesional->forceFill(['activo' => false])->save();

        $this->expectException(ProfesionalInactivoException::class);
        $contract->asegurarActivo($profesional->id);
    }
}
