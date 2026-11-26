<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class InscripcionConstraintTest extends InscripcionesAtencionesTestCase
{
    public function test_postgresql_rejects_overlapping_active_inscriptions(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La constraint EXCLUDE solo aplica a PostgreSQL.');
        }

        $scenario = $this->scenario('administracion');
        $this->crearInscripcion($scenario)->assertCreated();

        $this->expectException(QueryException::class);

        DB::table('inscripcion_sala')->insert([
            'paciente_id' => $scenario['paciente']->id,
            'tutor_responsable_id' => $scenario['tutor']->id,
            'sala_id' => $scenario['sala']->id,
            'sucursal_id' => $scenario['branch']->id,
            'servicio_id' => $scenario['servicio']->id,
            'tarifa_id' => $scenario['tarifa']->id,
            'categoria_tarifa_id' => $scenario['categoria']->id,
            'fecha_inicio' => '2026-11-15',
            'fecha_fin' => '2026-12-15',
            'monto_esperado' => '500.00',
            'estado' => 'activa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
