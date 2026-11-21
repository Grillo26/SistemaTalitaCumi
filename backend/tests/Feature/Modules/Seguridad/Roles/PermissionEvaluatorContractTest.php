<?php

namespace Tests\Feature\Modules\Seguridad\Roles;

use App\Modules\Seguridad\Contracts\EloquentPermissionEvaluator;
use App\Modules\Seguridad\Contracts\PermissionEvaluatorContract;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class PermissionEvaluatorContractTest extends SeguridadTestCase
{
    public function test_container_binds_real_evaluator(): void
    {
        $this->assertInstanceOf(
            EloquentPermissionEvaluator::class,
            app(PermissionEvaluatorContract::class),
        );
    }

    public function test_superadmin_permission_and_branch_scope(): void
    {
        $branch = $this->branch();
        $superadmin = $this->superadmin();
        $evaluator = app(PermissionEvaluatorContract::class);

        $this->assertTrue($evaluator->puede($superadmin->id, 'usuario.ver'));
        $this->assertTrue($evaluator->puede($superadmin->id, 'usuario.ver', $branch->id));
        $this->assertFalse($evaluator->puede($superadmin->id, 'usuario.ver', 999999));
        $this->assertFalse($evaluator->puede($superadmin->id, 'no.existe'));
        $this->assertContains('usuario.ver', $evaluator->permisos($superadmin->id));
    }

    public function test_administracion_requires_permission_and_branch(): void
    {
        $assigned = $this->branch(['codigo' => 'SUC-A']);
        $other = $this->branch(['codigo' => 'SUC-B']);
        $admin = $this->managedUser('administracion', $assigned);
        $evaluator = app(PermissionEvaluatorContract::class);

        $this->assertTrue($evaluator->puede($admin->id, 'usuario.ver'));
        $this->assertTrue($evaluator->puede($admin->id, 'usuario.ver', $assigned->id));
        $this->assertFalse($evaluator->puede($admin->id, 'usuario.ver', $other->id));
        $this->assertFalse($evaluator->puede($admin->id, 'usuario.crear'));
        $this->assertNotContains('rol.editar_permisos', $evaluator->permisos($admin->id));
    }

    public function test_recepcion_inactive_and_unknown_are_closed(): void
    {
        $branch = $this->branch();
        $reception = $this->managedUser('recepcion', $branch);
        $inactive = $this->managedUser('administracion', $branch, ['activo' => false]);
        $evaluator = app(PermissionEvaluatorContract::class);

        $this->assertFalse($evaluator->puede($reception->id, 'usuario.ver'));
        $this->assertNotContains('usuario.ver', $evaluator->permisos($reception->id));
        $this->assertFalse($evaluator->puede($inactive->id, 'usuario.ver'));
        $this->assertSame([], $evaluator->permisos($inactive->id));
        $this->assertFalse($evaluator->puede(999999, 'usuario.ver'));
        $this->assertSame([], $evaluator->permisos(999999));
    }
}
