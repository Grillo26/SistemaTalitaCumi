<?php

namespace Tests\Feature\Modules\Profesionales\Especialidades;

use App\Modules\Profesionales\Models\Especialidad;
use Database\Seeders\EspecialidadCatalogoSeeder;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class EspecialidadSeederTest extends ProfesionalesTestCase
{
    public function test_seeds_five_base_specialties_without_overwriting_changes(): void
    {
        $this->assertSame(5, Especialidad::query()->count());

        $psicologia = Especialidad::query()->where('nombre', 'Psicología')->firstOrFail();
        $psicologia->forceFill(['nombre' => 'Psicología Clínica'])->save();

        $this->seed(EspecialidadCatalogoSeeder::class);

        $this->assertSame(5, Especialidad::query()->count());
        $this->assertDatabaseHas('especialidad', ['nombre' => 'Psicología Clínica']);
        $this->assertDatabaseMissing('especialidad', ['nombre' => 'Psicología']);
    }
}
