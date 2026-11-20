<?php

namespace Tests\Feature\Modules\Organizacion\Configuracion;

use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ParameterDatabaseTest extends ConfiguracionTestCase
{
    public function test_database_rejects_duplicate_clave(): void
    {
        Parametro::factory()->create(['clave' => 'prueba.unica']);

        $this->expectException(QueryException::class);

        Parametro::factory()->create(['clave' => 'prueba.unica']);
    }

    public function test_table_has_no_soft_delete_or_branch_scope(): void
    {
        $this->assertFalse(Schema::hasColumn('configuracion', 'deleted_at'));
        $this->assertFalse(Schema::hasColumn('configuracion', 'sucursal_id'));
        $this->assertFalse(Schema::hasColumn('configuracion', 'activo'));
        $this->assertFalse(Schema::hasColumn('configuracion', 'created_at'));
    }

    public function test_postgres_rejects_unknown_type(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La restricción CHECK solo se aplica en PostgreSQL.');
        }

        $this->expectException(QueryException::class);

        DB::table('configuracion')->insert([
            'clave' => 'prueba.tipo.invalido',
            'categoria' => 'operacion',
            'tipo' => 'invalido',
            'valor' => 'x',
            'valor_defecto' => 'x',
        ]);
    }
}
