<?php

namespace Tests\Feature\Modules\Seguridad\Audit;

use App\Modules\Seguridad\Models\BitacoraEntry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class BitacoraImmutabilityTest extends SeguridadTestCase
{
    public function test_bitacora_cannot_be_updated_in_postgres(): void
    {
        $this->requirePostgres();

        $entry = $this->entry();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('inmutable');

        DB::table('bitacora')
            ->where('id', $entry->id)
            ->update(['accion' => 'ALTERADA']);
    }

    public function test_bitacora_cannot_be_deleted_in_postgres(): void
    {
        $this->requirePostgres();

        $entry = $this->entry();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('inmutable');

        DB::table('bitacora')->where('id', $entry->id)->delete();
    }

    private function entry(): BitacoraEntry
    {
        return BitacoraEntry::query()->create([
            'usuario_id' => null,
            'sucursal_id' => null,
            'accion' => 'PRUEBA_INMUTABILIDAD',
            'entidad' => 'test',
            'entidad_id' => 1,
            'datos_anteriores' => null,
            'datos_nuevos' => ['ok' => true],
            'motivo' => null,
            'ip' => '127.0.0.1',
            'modulo' => 'auditoria',
            'resultado' => 'exito',
            'critico' => false,
        ]);
    }

    private function requirePostgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('El trigger de inmutabilidad solo existe en PostgreSQL.');
        }
    }
}
