<?php

namespace Tests\Feature\Modules\Pacientes;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class PacientesDatabaseTest extends PacientesTestCase
{
    public function test_document_is_unique_but_multiple_nulls_are_allowed(): void
    {
        $branch = $this->branch();

        $this->paciente(['sucursal_registro_id' => $branch->id, 'ci' => null]);
        $this->paciente(['sucursal_registro_id' => $branch->id, 'ci' => null]);

        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id, 'ci' => 'ABC-1']);

        $this->expectException(QueryException::class);

        $this->paciente([
            'sucursal_registro_id' => $branch->id,
            'ci' => ' abc-1 ',
            'nombre' => 'Otro',
        ]);
    }

    public function test_normalized_columns_are_populated_by_the_model(): void
    {
        $branch = $this->branch();

        $paciente = $this->paciente([
            'sucursal_registro_id' => $branch->id,
            'nombre' => 'José',
            'apellido_paterno' => 'Núñez',
            'apellido_materno' => 'Pérez',
            'ci' => ' ABC-1 ',
        ]);

        $this->assertSame('jose', $paciente->nombre_normalizado);
        $this->assertSame('nunez', $paciente->apellido_paterno_normalizado);
        $this->assertSame('perez', $paciente->apellido_materno_normalizado);
        $this->assertSame('jose nunez perez', $paciente->nombre_completo_normalizado);
        $this->assertSame('abc-1', $paciente->documento_normalizado);
    }

    public function test_estado_check_rejects_invalid_values(): void
    {
        $branch = $this->branch();

        $this->expectException(QueryException::class);

        DB::table('paciente')->insert([
            'codigo' => 'PAC-X',
            'nombre' => 'Prueba',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'fecha_nacimiento' => '2020-01-01',
            'sexo' => 'F',
            'ci' => null,
            'tipo_diagnostico_id' => null,
            'sucursal_registro_id' => $branch->id,
            'estado' => 'invalido',
            'alergias' => null,
            'condiciones_medicas' => null,
            'observaciones' => null,
            'nombre_normalizado' => 'prueba',
            'apellido_paterno_normalizado' => 'prueba',
            'apellido_materno_normalizado' => null,
            'nombre_completo_normalizado' => 'prueba prueba',
            'documento_normalizado' => null,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_branch_foreign_key_is_enforced(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $foreignKeys = DB::selectOne('PRAGMA foreign_keys');
            if ((int) ($foreignKeys->foreign_keys ?? 0) === 0) {
                $this->markTestSkipped('SQLite sin foreign_keys habilitadas.');
            }
        }

        $this->expectException(QueryException::class);

        DB::table('paciente')->insert([
            'codigo' => 'PAC-Y',
            'nombre' => 'Prueba',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'fecha_nacimiento' => '2020-01-01',
            'sexo' => 'F',
            'ci' => null,
            'tipo_diagnostico_id' => null,
            'sucursal_registro_id' => 999999,
            'estado' => 'activo',
            'alergias' => null,
            'condiciones_medicas' => null,
            'observaciones' => null,
            'nombre_normalizado' => 'prueba',
            'apellido_paterno_normalizado' => 'prueba',
            'apellido_materno_normalizado' => null,
            'nombre_completo_normalizado' => 'prueba prueba',
            'documento_normalizado' => null,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
