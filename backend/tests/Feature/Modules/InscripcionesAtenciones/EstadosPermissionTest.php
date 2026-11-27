<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Support\EstadoPermission;
use Spatie\Permission\Models\Role;

final class EstadosPermissionTest extends InscripcionesAtencionesTestCase
{
    public function test_estado_permissions_matrix_has_no_role_hardcode_in_code(): void
    {
        $superadmin = Role::findByName('superadmin', 'web');
        $administracion = Role::findByName('administracion', 'web');
        $recepcion = Role::findByName('recepcion', 'web');

        $this->assertTrue($superadmin->hasPermissionTo(EstadoPermission::VIEW));
        $this->assertTrue($administracion->hasPermissionTo(EstadoPermission::REPORTS));
        $this->assertTrue($administracion->hasPermissionTo(EstadoPermission::INSCRIPTION_PAUSE));
        $this->assertTrue($recepcion->hasPermissionTo(EstadoPermission::VIEW));
        $this->assertTrue($recepcion->hasPermissionTo(EstadoPermission::INSCRIPTION_PAUSE));
        $this->assertFalse($recepcion->hasPermissionTo(EstadoPermission::REPORTS));
        $this->assertFalse($recepcion->hasPermissionTo(EstadoPermission::EXPORT));
        $this->assertFalse($recepcion->hasPermissionTo('paciente.cambiar_estado'));
        $this->assertFalse($recepcion->hasPermissionTo('paciente.retirar'));
        $this->assertTrue($administracion->hasPermissionTo('paciente.retirar'));
    }
}
