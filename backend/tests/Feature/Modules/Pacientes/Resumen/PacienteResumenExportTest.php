<?php

namespace Tests\Feature\Modules\Pacientes\Resumen;

use App\Modules\Pacientes\Models\BitacoraEntry;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PacienteResumenExportTest extends PacientesTestCase
{
    #[Test]
    public function administracion_exports_a_minimal_scoped_csv_and_audits_safe_filters(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branchA);

        $this->paciente([
            'sucursal_registro_id' => $branchA->id,
            'nombre' => 'María',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Quispe',
            'ci' => '81234567',
            'alergias' => 'Penicilina',
            'condiciones_medicas' => 'Asma',
            'observaciones' => 'No debe exportarse.',
        ]);
        $this->paciente([
            'sucursal_registro_id' => $branchB->id,
            'nombre' => 'Paciente',
            'apellido_paterno' => 'Ajeno',
            'ci' => '82345678',
        ]);

        $response = $this->get(
            '/api/pacientes/pacientes/exportar?search=maria&estado=activo&sucursal_registro_id='.$branchA->id,
        );

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Nombre completo', $content);
        $this->assertStringContainsString('María López Quispe', $content);
        $this->assertStringContainsString('81234567', $content);
        $this->assertStringNotContainsString('Ajeno', $content);
        $this->assertStringNotContainsString('Penicilina', $content);
        $this->assertStringNotContainsString('Asma', $content);
        $this->assertStringNotContainsString('No debe exportarse', $content);
        $this->assertStringNotContainsString('71234567', $content);

        $entry = BitacoraEntry::query()
            ->where('entidad', 'exportacion')
            ->where('accion', 'EXPORTAR')
            ->firstOrFail();

        $this->assertSame('pacientes_listado', $entry->datos_nuevos['recurso']);
        $this->assertSame(1, $entry->datos_nuevos['cantidad']);
        $this->assertTrue((bool) $entry->critico);
        $this->assertTrue($entry->datos_nuevos['filtros']['search_presente']);
        $this->assertSame(5, $entry->datos_nuevos['filtros']['search_longitud']);
        $this->assertArrayNotHasKey('search', $entry->datos_nuevos['filtros']);
        $this->assertSame('csv', $entry->datos_nuevos['filtros']['formato']);
        $this->assertSame($branchA->id, $entry->sucursal_id);
    }

    #[Test]
    public function recepcion_cannot_export_the_patient_list(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);

        $this->get('/api/pacientes/pacientes/exportar')
            ->assertStatus(403);
    }

    #[Test]
    public function export_limit_and_format_are_validated(): void
    {
        $branch = $this->branch();
        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);
        config(['pacientes.export_max_rows' => 2]);

        foreach (range(1, 3) as $i) {
            $this->paciente([
                'sucursal_registro_id' => $branch->id,
                'nombre' => 'Paciente '.$i,
                'ci' => '9000000'.$i,
            ]);
        }

        $this->get('/api/pacientes/pacientes/exportar')
            ->assertStatus(422)
            ->assertJsonPath('code', 'PACIENTES_EXPORT_TOO_LARGE');

        $this->get('/api/pacientes/pacientes/exportar?formato=pdf')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['formato']);
    }

    #[Test]
    public function csv_escapes_formula_injection(): void
    {
        $branch = $this->branch();
        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $this->paciente([
            'sucursal_registro_id' => $branch->id,
            'nombre' => '=SUM(A1:A2)',
            'apellido_paterno' => 'Prueba',
            'ci' => '90000009',
        ]);

        $content = $this->get('/api/pacientes/pacientes/exportar')->streamedContent();

        $this->assertStringContainsString("'=SUM(A1:A2)", $content);
    }
}
