<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Models\User;
use App\Modules\Organizacion\Events\BranchActivated;
use App\Modules\Organizacion\Events\BranchCreated;
use App\Modules\Organizacion\Events\BranchDeactivated;
use App\Modules\Organizacion\Events\BranchUpdated;
use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Support\BranchSnapshot;

/**
 * RF-011 / RNF-013: eventos de dominio y bitácora.
 */
final class BranchAuditTest extends OrganizacionTestCase
{
    public function test_all_branch_events_are_recorded_in_bitacora(): void
    {
        $branch = Branch::factory()->create([
            'telefono' => '70000000',
            'activo' => true,
            'version' => 1,
        ]);

        $user = User::factory()->create();

        event(new BranchCreated(
            branchId: $branch->id,
            snapshot: BranchSnapshot::from($branch),
            actorId: $user->id,
        ));

        $beforeUpdate = BranchSnapshot::from($branch);
        $branch->telefono = '71111111';
        $branch->version = 2;
        $branch->save();
        $branch->refresh();

        event(new BranchUpdated(
            branchId: $branch->id,
            before: $beforeUpdate,
            after: BranchSnapshot::from($branch),
            changedFields: ['telefono'],
            actorId: $user->id,
        ));

        $beforeDeactivation = BranchSnapshot::from($branch);
        $branch->activo = false;
        $branch->version = 3;
        $branch->save();
        $branch->refresh();

        event(new BranchDeactivated(
            branchId: $branch->id,
            before: $beforeDeactivation,
            after: BranchSnapshot::from($branch),
            reason: 'Cierre temporal',
            actorId: $user->id,
        ));

        $beforeActivation = BranchSnapshot::from($branch);
        $branch->activo = true;
        $branch->version = 4;
        $branch->save();
        $branch->refresh();

        event(new BranchActivated(
            branchId: $branch->id,
            before: $beforeActivation,
            after: BranchSnapshot::from($branch),
            reason: 'Reapertura',
            actorId: $user->id,
        ));

        $entries = BitacoraEntry::query()->where('entidad', 'sucursal')->orderBy('id')->get();

        $this->assertCount(4, $entries);
        $this->assertSame(['CREAR', 'ACTUALIZAR', 'DESACTIVAR', 'ACTIVAR'], $entries->pluck('accion')->all());
        $this->assertSame($branch->id, $entries->first()->sucursal_id);
        $this->assertSame($user->id, $entries->first()->usuario_id);
        $this->assertSame('70000000', $entries[1]->datos_anteriores['telefono']);
        $this->assertSame('71111111', $entries[1]->datos_nuevos['telefono']);
        $this->assertSame('Cierre temporal', $entries[2]->motivo);
        $this->assertSame('Reapertura', $entries[3]->motivo);
        $this->assertTrue($entries[2]->datos_anteriores['activo']);
        $this->assertFalse($entries[2]->datos_nuevos['activo']);
        $this->assertFalse($entries[3]->datos_anteriores['activo']);
        $this->assertTrue($entries[3]->datos_nuevos['activo']);
    }
}
