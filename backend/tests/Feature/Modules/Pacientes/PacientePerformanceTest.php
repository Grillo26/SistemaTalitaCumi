<?php

namespace Tests\Feature\Modules\Pacientes;

use App\Modules\Pacientes\Queries\Registro\ListPacientesQuery;
use Illuminate\Support\Facades\DB;

final class PacientePerformanceTest extends PacientesTestCase
{
    public function test_search_with_5000_patients_is_under_one_second(): void
    {
        $branch = $this->branch();
        $now = now();

        DB::transaction(function () use ($branch, $now): void {
            for ($chunk = 0; $chunk < 10; $chunk++) {
                $rows = [];

                for ($i = 1; $i <= 500; $i++) {
                    $number = ($chunk * 500) + $i;
                    $nombre = 'Paciente '.$number;

                    $rows[] = [
                        'codigo' => 'PAC-P'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                        'nombre' => $nombre,
                        'apellido_paterno' => 'Prueba',
                        'apellido_materno' => 'Rendimiento',
                        'fecha_nacimiento' => '2020-01-01',
                        'sexo' => $number % 2 === 0 ? 'F' : 'M',
                        'ci' => 'PERF-'.$number,
                        'tipo_diagnostico_id' => null,
                        'sucursal_registro_id' => $branch->id,
                        'estado' => 'activo',
                        'alergias' => null,
                        'condiciones_medicas' => null,
                        'observaciones' => null,
                        'nombre_normalizado' => mb_strtolower($nombre),
                        'apellido_paterno_normalizado' => 'prueba',
                        'apellido_materno_normalizado' => 'rendimiento',
                        'nombre_completo_normalizado' => mb_strtolower($nombre.' prueba rendimiento'),
                        'documento_normalizado' => 'perf-'.$number,
                        'version' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('paciente')->insert($rows);
            }
        });

        $this->actingAsSuperadmin();

        $start = microtime(true);

        $paginator = app(ListPacientesQuery::class)(
            search: '4999',
            estado: null,
            tipoDiagnosticoId: null,
            sinDiagnostico: null,
            sucursalRegistroId: null,
            page: 1,
            perPage: 10,
            sort: 'nombre_completo',
            direction: 'asc',
            actor: auth()->user(),
        );

        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(1, $paginator->total());
        $this->assertLessThan(1.0, $elapsed, "La búsqueda tardó {$elapsed} s.");
    }
}
