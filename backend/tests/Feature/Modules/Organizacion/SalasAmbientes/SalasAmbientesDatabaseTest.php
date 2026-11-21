<?php

namespace Tests\Feature\Modules\Organizacion\SalasAmbientes;

use App\Modules\Organizacion\Models\Ambiente;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Models\TurnoCatalogo;
use Illuminate\Database\QueryException;

/**
 * Verifica los índices únicos de BD (SQLite y PostgreSQL).
 */
final class SalasAmbientesDatabaseTest extends SalasAmbientesTestCase
{
    public function test_database_rejects_duplicate_tipo_sala_name(): void
    {
        TipoSala::query()->create(['nombre' => 'Tipo Único', 'activo' => true, 'version' => 1]);

        $this->expectException(QueryException::class);

        TipoSala::query()->create(['nombre' => '  TIPO ÚNICO  ', 'activo' => true, 'version' => 1]);
    }

    public function test_database_rejects_duplicate_turno_name(): void
    {
        TurnoCatalogo::query()->create(['nombre' => 'Turno Único', 'orden' => 8, 'activo' => true, 'version' => 1]);

        $this->expectException(QueryException::class);

        TurnoCatalogo::query()->create(['nombre' => '  TURNO ÚNICO  ', 'orden' => 9, 'activo' => true, 'version' => 1]);
    }

    public function test_database_rejects_duplicate_sala_combination(): void
    {
        $branch = $this->branch();
        $tipo = $this->tipoSala('Preescolar');
        $turno = $this->turno('Mañana');
        $area = $this->area();

        Sala::query()->create([
            'sucursal_id' => $branch->id,
            'area_id' => $area->id,
            'tipo_sala_id' => $tipo->id,
            'turno_catalogo_id' => $turno->id,
            'nombre' => 'Sala A',
            'activo' => true,
            'version' => 1,
        ]);

        $this->expectException(QueryException::class);

        Sala::query()->create([
            'sucursal_id' => $branch->id,
            'area_id' => $area->id,
            'tipo_sala_id' => $tipo->id,
            'turno_catalogo_id' => $turno->id,
            'nombre' => 'Sala B',
            'activo' => true,
            'version' => 1,
        ]);
    }

    public function test_database_rejects_duplicate_ambiente_name_in_same_branch(): void
    {
        $branch = $this->branch();

        Ambiente::query()->create([
            'sucursal_id' => $branch->id,
            'nombre' => 'Consultorio 1',
            'uso' => 'terapia_individual',
            'activo' => true,
            'version' => 1,
        ]);

        $this->expectException(QueryException::class);

        Ambiente::query()->create([
            'sucursal_id' => $branch->id,
            'nombre' => ' CONSULTORIO 1 ',
            'uso' => 'mixto',
            'activo' => true,
            'version' => 1,
        ]);
    }

    public function test_database_allows_same_ambiente_name_in_another_branch(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $a = Ambiente::query()->create([
            'sucursal_id' => $branchA->id,
            'nombre' => 'Consultorio 1',
            'uso' => 'terapia_individual',
            'activo' => true,
            'version' => 1,
        ]);
        $b = Ambiente::query()->create([
            'sucursal_id' => $branchB->id,
            'nombre' => 'Consultorio 1',
            'uso' => 'mixto',
            'activo' => true,
            'version' => 1,
        ]);

        $this->assertNotSame($a->id, $b->id);
    }
}
