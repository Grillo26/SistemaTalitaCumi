<?php

namespace Tests\Feature\Modules\Profesionales;

use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use Illuminate\Database\QueryException;

final class ProfesionalesDatabaseTest extends ProfesionalesTestCase
{
    public function test_database_rejects_duplicate_document_case_insensitively(): void
    {
        Profesional::query()->create([
            'nombre_completo' => 'Profesional Uno',
            'ci' => 'ABC123',
            'activo' => true,
            'version' => 1,
        ]);

        $this->expectException(QueryException::class);

        Profesional::query()->create([
            'nombre_completo' => 'Profesional Dos',
            'ci' => ' abc123 ',
            'activo' => true,
            'version' => 1,
        ]);
    }

    public function test_database_rejects_two_active_principals(): void
    {
        $profesional = $this->profesional();
        $other = Especialidad::factory()->create();

        $this->expectException(QueryException::class);

        ProfesionalEspecialidad::query()->create([
            'profesional_id' => $profesional->id,
            'especialidad_id' => $other->id,
            'tipo' => TipoEspecialidad::PRINCIPAL,
            'activo' => true,
        ]);
    }

    public function test_database_rejects_duplicate_active_specialty_relation(): void
    {
        $profesional = $this->profesional();
        $especialidad = $this->especialidad();

        $this->expectException(QueryException::class);

        ProfesionalEspecialidad::query()->create([
            'profesional_id' => $profesional->id,
            'especialidad_id' => $especialidad->id,
            'tipo' => TipoEspecialidad::SECUNDARIA,
            'activo' => true,
        ]);
    }

    public function test_inactive_specialty_relation_can_be_reused(): void
    {
        $profesional = $this->profesional();
        $especialidad = $this->especialidad();

        $existing = ProfesionalEspecialidad::query()
            ->where('profesional_id', $profesional->id)
            ->where('especialidad_id', $especialidad->id)
            ->firstOrFail();

        $existing->forceFill(['activo' => false])->save();

        $new = ProfesionalEspecialidad::query()->create([
            'profesional_id' => $profesional->id,
            'especialidad_id' => $especialidad->id,
            'tipo' => TipoEspecialidad::SECUNDARIA,
            'activo' => true,
        ]);

        $this->assertTrue($new->activo);
    }

    public function test_database_rejects_duplicate_active_branch_relation(): void
    {
        $profesional = $this->profesional();
        $branch = $this->branch();
        $this->assignBranch($profesional, $branch);

        $this->expectException(QueryException::class);

        ProfesionalSucursal::query()->create([
            'profesional_id' => $profesional->id,
            'sucursal_id' => $branch->id,
            'activo' => true,
            'version' => 1,
        ]);
    }
}
