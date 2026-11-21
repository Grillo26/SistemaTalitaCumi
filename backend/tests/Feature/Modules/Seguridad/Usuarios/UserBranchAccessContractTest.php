<?php

namespace Tests\Feature\Modules\Seguridad\Usuarios;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Seguridad\Contracts\EloquentUserBranchAccess;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class UserBranchAccessContractTest extends SeguridadTestCase
{
    public function test_container_binds_real_implementation(): void
    {
        $this->assertInstanceOf(
            EloquentUserBranchAccess::class,
            app(UserBranchAccessContract::class),
        );
    }

    public function test_superadmin_gets_all_active_branches(): void
    {
        $first = $this->branch(['codigo' => 'SUC-001']);
        $second = $this->branch(['codigo' => 'SUC-002']);
        $this->branch(['codigo' => 'SUC-003', 'activo' => false]);

        $superadmin = $this->superadmin();

        $access = app(UserBranchAccessContract::class);

        $ids = $access->accesibles($superadmin->id);

        sort($ids);

        $this->assertSame([$first->id, $second->id], $ids);
        $this->assertTrue($access->puedeAcceder($superadmin->id, $first->id));
        $this->assertFalse($access->puedeAcceder($superadmin->id, 999999));
    }

    public function test_user_without_global_scope_gets_only_active_assigned_branches(): void
    {
        $active = $this->branch(['codigo' => 'SUC-001']);
        $inactive = $this->branch(['codigo' => 'SUC-002', 'activo' => false]);

        $admin = $this->administracion();
        $admin->branches()->attach([$active->id, $inactive->id]);

        $access = app(UserBranchAccessContract::class);

        $this->assertSame([$active->id], $access->accesibles($admin->id));
        $this->assertTrue($access->puedeAcceder($admin->id, $active->id));
        $this->assertFalse($access->puedeAcceder($admin->id, $inactive->id));
    }

    public function test_inactive_or_unknown_user_gets_empty_scope(): void
    {
        $branch = $this->branch();
        $inactive = $this->administracion(['activo' => false]);
        $inactive->branches()->attach($branch->id);

        $access = app(UserBranchAccessContract::class);

        $this->assertSame([], $access->accesibles($inactive->id));
        $this->assertSame([], $access->accesibles(999999));
    }
}
