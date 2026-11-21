<?php

namespace Tests\Feature\Modules\Seguridad\Auditoria;

use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use App\Modules\Seguridad\Models\BitacoraEntry;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class AuditRecorderContractTest extends SeguridadTestCase
{
    public function test_sensitive_query_keeps_references_only(): void
    {
        $actor = $this->superadmin();
        $branch = $this->branch();
        $recorder = app(AuditRecorderContract::class);

        $recorder->registrarConsultaSensible(
            entidad: 'paciente',
            entidadId: 10,
            sucursalId: $branch->id,
            contexto: [
                'entidad' => 'paciente',
                'entidad_id' => 10,
                'recurso' => 'ficha_resumen',
                'contenido' => 'Texto clínico que no debe guardarse',
                'password' => 'no-guardar',
            ],
            usuarioId: $actor->id,
            ip: '203.0.113.50',
        );

        $entry = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', 10)
            ->where('accion', 'CONSULTAR')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($actor->id, $entry->usuario_id);
        $this->assertSame($branch->id, $entry->sucursal_id);
        $this->assertSame('pacientes', $entry->modulo);
        $this->assertSame('203.0.113.50', $entry->ip);
        $this->assertFalse($entry->critico);
        $this->assertSame('ficha_resumen', $entry->datos_nuevos['contexto']['recurso']);
        $this->assertArrayNotHasKey('contenido', $entry->datos_nuevos['contexto']);
        $this->assertArrayNotHasKey('password', $entry->datos_nuevos['contexto']);
    }

    public function test_export_is_audited_as_critical_without_data_content(): void
    {
        $actor = $this->superadmin();
        $branch = $this->branch();
        $recorder = app(AuditRecorderContract::class);

        $recorder->registrarExportacion(
            recurso: 'paciente',
            filtros: ['estado' => 'activo', 'sucursal_id' => $branch->id],
            cantidad: 25,
            sucursalId: $branch->id,
            usuarioId: $actor->id,
            ip: '203.0.113.51',
        );

        $entry = BitacoraEntry::query()
            ->where('entidad', 'exportacion')
            ->where('accion', 'EXPORTAR')
            ->latest('id')
            ->firstOrFail();

        $this->assertTrue($entry->critico);
        $this->assertSame('auditoria', $entry->modulo);
        $this->assertSame('paciente', $entry->datos_nuevos['recurso']);
        $this->assertSame(25, $entry->datos_nuevos['cantidad']);
        $this->assertArrayHasKey('filtros', $entry->datos_nuevos);
    }

    public function test_bitacora_query_is_audited(): void
    {
        $actor = $this->superadmin();
        $recorder = app(AuditRecorderContract::class);

        $recorder->registrarConsultaBitacora(
            filtros: ['modulo' => 'usuarios'],
            cantidad: 5,
            usuarioId: $actor->id,
            ip: '203.0.113.52',
        );

        $entry = BitacoraEntry::query()
            ->where('entidad', 'bitacora')
            ->where('accion', 'CONSULTAR')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($actor->id, $entry->usuario_id);
        $this->assertSame('auditoria', $entry->modulo);
        $this->assertSame('usuarios', $entry->datos_nuevos['filtros']['modulo']);
        $this->assertSame(5, $entry->datos_nuevos['cantidad']);
    }
}
