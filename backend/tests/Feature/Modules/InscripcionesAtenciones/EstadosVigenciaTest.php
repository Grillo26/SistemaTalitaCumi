<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\Pacientes\Models\Paciente;

final class EstadosVigenciaTest extends VigenciasTestCase
{
    public function test_paused_inscriptions_and_retired_patients_are_excluded_from_vigencia(): void
    {
        $escenario = $this->scenario('administracion');
        $pausada = $this->crearInscripcionPorVencer($escenario);
        $otra = $this->crearInscripcionPorVencer(
            array_merge($escenario, ['sala' => $this->sala($escenario['branch'])]),
        );

        InscripcionSala::query()->whereKey($pausada['id'])->update(['estado' => 'en_pausa']);
        Paciente::query()->whereKey($escenario['paciente']->id)->update(['estado' => 'retirado']);

        $this->getJson('/api/inscripciones-atenciones/vigencias/panel')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->getJson('/api/inscripciones-atenciones/vigencias/reportes')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->assertNotSame($pausada['id'], $otra['id']);
    }
}
