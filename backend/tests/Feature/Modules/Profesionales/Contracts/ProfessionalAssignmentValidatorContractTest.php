<?php

namespace Tests\Feature\Modules\Profesionales\Contracts;

use App\Modules\Profesionales\Actions\Directorio\DeactivateProfesionalAction;
use App\Modules\Profesionales\Actions\Directorio\DeactivateProfesionalSucursalAction;
use App\Modules\Profesionales\Contracts\ProfessionalAssignmentValidatorContract;
use App\Modules\Profesionales\Exceptions\ProfesionalNotAssignableException;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class ProfessionalAssignmentValidatorContractTest extends ProfesionalesTestCase
{
    public function test_valid_assignment_is_allowed(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branch);

        $resultado = app(ProfessionalAssignmentValidatorContract::class)
            ->puedeAsignar($profesional->id, $profesional->especialidades()->first()->id, $branch->id);

        $this->assertTrue($resultado->puede);
        $this->assertSame('ASIGNACION_VALIDA', $resultado->codigo);
    }

    public function test_inactive_professional_is_rejected(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional(['activo' => false]);
        $this->assignBranch($profesional, $branch);

        $resultado = app(ProfessionalAssignmentValidatorContract::class)
            ->puedeAsignar($profesional->id, $this->specialtyId($profesional), $branch->id);

        $this->assertFalse($resultado->puede);
        $this->assertSame('PROFESIONAL_INACTIVO', $resultado->codigo);
        $this->assertStringContainsString('inactivo', $resultado->mensaje);
    }

    public function test_specialty_not_assigned_is_rejected(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branch);
        $otra = Especialidad::factory()->create();

        $resultado = app(ProfessionalAssignmentValidatorContract::class)
            ->puedeAsignar($profesional->id, $otra->id, $branch->id);

        $this->assertFalse($resultado->puede);
        $this->assertSame('ESPECIALIDAD_NO_ASIGNADA', $resultado->codigo);
    }

    public function test_inactive_specialty_is_rejected(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branch);
        $inactiva = Especialidad::factory()->create(['activo' => false]);

        ProfesionalEspecialidad::query()->create([
            'profesional_id' => $profesional->id,
            'especialidad_id' => $inactiva->id,
            'tipo' => 'secundaria',
            'activo' => true,
        ]);

        $resultado = app(ProfessionalAssignmentValidatorContract::class)
            ->puedeAsignar($profesional->id, $inactiva->id, $branch->id);

        $this->assertFalse($resultado->puede);
        $this->assertSame('ESPECIALIDAD_INACTIVA', $resultado->codigo);
    }

    public function test_branch_without_relation_is_rejected(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional();

        $resultado = app(ProfessionalAssignmentValidatorContract::class)
            ->puedeAsignar($profesional->id, $this->specialtyId($profesional), $branch->id);

        $this->assertFalse($resultado->puede);
        $this->assertSame('SUCURSAL_NO_ASIGNADA', $resultado->codigo);
    }

    public function test_inactive_branch_is_rejected_even_with_cached_matrix(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branch);
        $validator = app(ProfessionalAssignmentValidatorContract::class);
        $especialidadId = $this->specialtyId($profesional);

        $this->assertTrue($validator->puedeAsignar($profesional->id, $especialidadId, $branch->id)->puede);

        $branch->forceFill(['activo' => false])->save();

        $resultado = $validator->puedeAsignar($profesional->id, $especialidadId, $branch->id);

        $this->assertFalse($resultado->puede);
        $this->assertSame('SUCURSAL_INACTIVA', $resultado->codigo);
    }

    public function test_cache_is_invalidated_when_professional_is_deactivated(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branch);
        $validator = app(ProfessionalAssignmentValidatorContract::class);
        $especialidadId = $this->specialtyId($profesional);
        $actor = $this->userWithRole('superadmin');

        $this->assertTrue($validator->puedeAsignar($profesional->id, $especialidadId, $branch->id)->puede);

        app(DeactivateProfesionalAction::class)(
            $profesional,
            'Prueba de invalidación',
            1,
            $actor,
        );

        $resultado = $validator->puedeAsignar($profesional->id, $especialidadId, $branch->id);

        $this->assertFalse($resultado->puede);
        $this->assertSame('PROFESIONAL_INACTIVO', $resultado->codigo);
    }

    public function test_cache_is_invalidated_when_branch_relation_is_deactivated(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branch);
        $validator = app(ProfessionalAssignmentValidatorContract::class);
        $especialidadId = $this->specialtyId($profesional);
        $actor = $this->userWithRole('superadmin');

        $this->assertTrue($validator->puedeAsignar($profesional->id, $especialidadId, $branch->id)->puede);

        app(DeactivateProfesionalSucursalAction::class)(
            $profesional,
            $branch->id,
            'Cambio de sede',
            1,
            $actor,
        );

        $resultado = $validator->puedeAsignar($profesional->id, $especialidadId, $branch->id);

        $this->assertFalse($resultado->puede);
        $this->assertSame('SUCURSAL_NO_ASIGNADA', $resultado->codigo);
    }

    public function test_asegurar_asignable_throws_with_reason(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional(['activo' => false]);
        $this->assignBranch($profesional, $branch);

        try {
            app(ProfessionalAssignmentValidatorContract::class)
                ->asegurarAsignable($profesional->id, $this->specialtyId($profesional), $branch->id);
            $this->fail('Se esperaba ProfesionalNotAssignableException.');
        } catch (ProfesionalNotAssignableException $exception) {
            $this->assertSame('PROFESIONAL_INACTIVO', $exception->resultado()->codigo);
            $this->assertSame(409, $exception->status());
        }
    }

    private function specialtyId($profesional): int
    {
        return (int) ProfesionalEspecialidad::query()
            ->where('profesional_id', $profesional->id)
            ->where('activo', true)
            ->where('tipo', 'principal')
            ->value('especialidad_id');
    }
}
