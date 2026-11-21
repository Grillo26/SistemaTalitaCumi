<?php

namespace Tests\Feature\Modules\Pacientes\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Models\Tutor;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class TutorExportTest extends PacientesTestCase
{
    public function test_administracion_exports_scoped_tutors_and_audits(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $parentescoId = $this->parentescoId('Madre');

        $tutorA = Tutor::factory()->create([
            'nombre_completo' => 'Ana Export',
            'sucursal_registro_id' => $branchA->id,
            'parentesco_id' => $parentescoId,
            'telefono' => '71234567',
        ]);
        $tutorB = Tutor::factory()->create([
            'nombre_completo' => 'Bruno Export',
            'sucursal_registro_id' => $branchB->id,
            'parentesco_id' => $parentescoId,
            'telefono' => '70001111',
        ]);

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branchA);

        $response = $this->get('/api/pacientes/tutores/exportar?search=Export&sucursal_registro_id='.$branchA->id)
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Ana Export', $csv);
        $this->assertStringContainsString('71234567', $csv);
        $this->assertStringNotContainsString('Bruno Export', $csv);
        $this->assertStringContainsString('Teléfono principal', $csv);

        $audit = BitacoraEntry::query()
            ->where('entidad', 'exportacion')
            ->where('accion', 'EXPORTAR')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('auditoria', $audit->modulo);
        $this->assertTrue((bool) $audit->critico);
        $this->assertSame('tutores', $audit->datos_nuevos['recurso']);
        $this->assertSame(1, $audit->datos_nuevos['cantidad']);
        $this->assertSame('Export', $audit->datos_nuevos['filtros']['search']);
        $this->assertSame('csv', $audit->datos_nuevos['filtros']['formato']);
        $this->assertSame($branchA->id, $audit->sucursal_id);

        $this->assertNotSame($tutorA->id, $tutorB->id);
    }

    public function test_recepcion_cannot_export_tutors(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);

        $this->get('/api/pacientes/tutores/exportar')->assertStatus(403);
    }

    public function test_export_masks_phone_without_contact_permission(): void
    {
        $branch = $this->branch();
        $tutor = Tutor::factory()->create([
            'nombre_completo' => 'Tutor Enmascarado',
            'sucursal_registro_id' => $branch->id,
            'parentesco_id' => $this->parentescoId('Padre'),
            'telefono' => '71234567',
        ]);

        $this->actAsExporter($branch->id);

        $csv = $this->get('/api/pacientes/tutores/exportar')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Tutor Enmascarado', $csv);
        $this->assertStringContainsString('712***67', $csv);
        $this->assertStringNotContainsString('71234567', $csv);
        $this->assertNotSame(0, $tutor->id);
    }

    public function test_csv_escapes_formula_like_values(): void
    {
        $branch = $this->branch();
        $tutor = Tutor::factory()->create([
            'nombre_completo' => '=HYPERLINK("http://example.test")',
            'sucursal_registro_id' => $branch->id,
            'parentesco_id' => $this->parentescoId('Otro'),
            'telefono' => '70002222',
        ]);

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $csv = $this->get('/api/pacientes/tutores/exportar')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertNotSame(0, $tutor->id);
    }

    private function parentescoId(string $name): int
    {
        return (int) Parentesco::query()->where('nombre', $name)->value('id');
    }

    private function actAsExporter(int $branchId): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('tutor.ver', 'web');
        Permission::findOrCreate('tutor.exportar', 'web');

        $role = Role::findOrCreate('exportador_tutores', 'web');
        $role->syncPermissions(['tutor.ver', 'tutor.exportar']);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->branches()->syncWithoutDetaching([$branchId]);

        Sanctum::actingAs($user);

        return $user;
    }
}
