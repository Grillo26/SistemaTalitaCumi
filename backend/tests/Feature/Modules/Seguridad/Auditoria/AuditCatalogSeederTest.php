<?php

namespace Tests\Feature\Modules\Seguridad\Auditoria;

use App\Modules\Seguridad\Models\AccionAuditada;
use Database\Seeders\AccionAuditadaSeeder;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class AuditCatalogSeederTest extends SeguridadTestCase
{
    public function test_catalog_is_seeded_with_known_actions(): void
    {
        $this->assertGreaterThan(30, AccionAuditada::query()->count());
        $this->assertTrue(AccionAuditada::query()
            ->where('modulo', 'usuarios')
            ->where('entidad', 'usuario')
            ->where('accion', 'DESACTIVAR')
            ->where('critico', true)
            ->exists());
        $this->assertTrue(AccionAuditada::query()
            ->where('modulo', 'roles')
            ->where('entidad', 'matriz_permisos')
            ->where('accion', 'EDITAR_PERMISOS')
            ->where('critico', true)
            ->exists());
        $this->assertTrue(AccionAuditada::query()
            ->where('modulo', 'auditoria')
            ->where('entidad', 'bitacora')
            ->where('accion', 'CONSULTAR')
            ->exists());
    }

    public function test_catalog_seeder_is_idempotent(): void
    {
        $before = AccionAuditada::query()->count();

        $this->seed(AccionAuditadaSeeder::class);

        $this->assertSame($before, AccionAuditada::query()->count());
    }
}
