<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

final class PaqueteAuthorizationTest extends PaquetesSesionesTestCase
{
    public function test_permission_and_audit_catalog_are_seeded(): void
    {
        foreach ([
            'paquete.ver',
            'paquete.crear',
            'paquete.editar',
            'paquete.renovar',
            'paquete.retirar',
            'paquete.exportar',
        ] as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission]);
        }

        $this->assertDatabaseHas('accion_auditada', [
            'modulo' => 'inscripciones',
            'entidad' => 'paquete_sesiones',
            'accion' => 'RETIRAR',
            'critico' => true,
        ]);

        $recepcion = Role::findByName('recepcion', 'web');
        $administracion = Role::findByName('administracion', 'web');

        $this->assertTrue($recepcion->hasPermissionTo('paquete.renovar'));
        $this->assertFalse($recepcion->hasPermissionTo('paquete.editar'));
        $this->assertFalse($recepcion->hasPermissionTo('paquete.retirar'));
        $this->assertTrue($administracion->hasPermissionTo('paquete.editar'));
        $this->assertTrue($administracion->hasPermissionTo('paquete.retirar'));
    }

    public function test_reception_can_view_create_and_renew_but_not_edit_or_withdraw(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones')->assertOk();
        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'])
            ->assertOk();

        $this->patchJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'], [
            'dias_semana' => [1, 3],
            'hora' => '08:00',
        ])->assertForbidden();

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
        ])->assertForbidden();

        $this->deleteJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'])
            ->assertForbidden();

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/renovar', [])
            ->assertCreated();
    }

    public function test_user_without_permissions_is_denied(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $sinPermisos = User::factory()->create();
        $this->withBranch($sinPermisos, $escenario['branch']);
        Sanctum::actingAs($sinPermisos);

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones')->assertForbidden();
        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'])
            ->assertForbidden();
    }

    public function test_administracion_can_edit_and_withdraw(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->patchJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'], [
            'dias_semana' => [2],
            'hora' => '11:00',
        ])->assertOk();

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
        ])->assertOk();
    }
}
