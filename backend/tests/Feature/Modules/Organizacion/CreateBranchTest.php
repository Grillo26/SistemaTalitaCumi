<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Branch;
use Illuminate\Database\QueryException;

final class CreateBranchTest extends OrganizacionTestCase
{
    public function test_superadmin_creates_branch_with_normalized_identity_and_audit(): void
    {
        $user = $this->actingAsSuperadmin();

        $response = $this->postJson('/api/organizacion/sucursales', [
            'codigo' => '  suc-001  ',
            'nombre' => '  Sucursal   Central  ',
            'direccion' => 'Calle 1',
            'telefono' => '+591 70000000',
            'email' => 'central@example.com',
            'activo' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.codigo', 'SUC-001')
            ->assertJsonPath('data.nombre', 'Sucursal Central')
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.estado', 'activa')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('can.create', true);

        $this->assertDatabaseHas('sucursal', [
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Central',
            'activo' => true,
        ]);

        $audit = BitacoraEntry::query()->where('entidad', 'sucursal')->sole();

        $this->assertSame($user->id, $audit->usuario_id);
        $this->assertSame('CREAR', $audit->accion);
        $this->assertSame('sucursal', $audit->entidad);
        $this->assertSame('SUC-001', $audit->datos_nuevos['codigo']);
    }

    public function test_creates_inactive_branch_when_explicitly_requested(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/sucursales', [
            'codigo' => 'SUC-002',
            'nombre' => 'Sucursal Norte',
            'activo' => false,
        ])
            ->assertCreated()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.estado', 'inactiva');
    }

    public function test_rejects_duplicate_code_case_and_space_insensitive(): void
    {
        Branch::factory()->create([
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Central',
        ]);

        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/sucursales', [
            'codigo' => '  suc-001 ',
            'nombre' => 'Otra sucursal',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('codigo');
    }

    public function test_rejects_duplicate_name_case_and_space_insensitive(): void
    {
        Branch::factory()->create([
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Central',
        ]);

        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/sucursales', [
            'codigo' => 'SUC-900',
            'nombre' => '  sucursal   central ',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');
    }

    public function test_rejects_missing_required_fields(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/sucursales', [
            'codigo' => 'SUC-003',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');
    }

    public function test_rejects_invalid_phone_and_email_formats(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/organizacion/sucursales', [
            'codigo' => 'SUC-004',
            'nombre' => 'Sucursal Sur',
            'telefono' => 'abc',
            'email' => 'no-es-email',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['telefono', 'email']);
    }

    public function test_reception_cannot_create_branch(): void
    {
        $this->actingAsRole('recepcion');

        $this->postJson('/api/organizacion/sucursales', [
            'codigo' => 'SUC-005',
            'nombre' => 'Sucursal Prohibida',
        ])->assertForbidden();
    }

    public function test_administracion_cannot_create_branch(): void
    {
        $this->actingAsRole('administracion');

        $this->postJson('/api/organizacion/sucursales', [
            'codigo' => 'SUC-006',
            'nombre' => 'Sucursal Prohibida',
        ])->assertForbidden();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/organizacion/sucursales', [
            'codigo' => 'SUC-007',
            'nombre' => 'Sucursal Anónima',
        ])->assertUnauthorized();
    }

    public function test_database_unique_index_blocks_duplicate_code_without_app_check(): void
    {
        Branch::factory()->create([
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Central',
        ]);

        $this->expectException(QueryException::class);

        Branch::query()->create([
            'codigo' => 'suc-001',
            'nombre' => 'Otra sucursal',
            'activo' => true,
            'version' => 1,
        ]);
    }
}
