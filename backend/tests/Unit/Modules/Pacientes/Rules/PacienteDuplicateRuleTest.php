<?php

namespace Tests\Unit\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Enums\TipoCoincidencia;
use App\Modules\Pacientes\Rules\PacienteDuplicateRule;
use PHPUnit\Framework\TestCase;

final class PacienteDuplicateRuleTest extends TestCase
{
    private function input(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'maria',
            'apellido_paterno' => 'lopez',
            'apellido_materno' => 'quispe',
            'fecha_nacimiento' => '2020-05-10',
            'tutor_principal_id' => null,
        ], $overrides);
    }

    private function candidate(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'nombre' => 'maria',
            'apellido_paterno' => 'lopez',
            'apellido_materno' => 'quispe',
            'fecha_nacimiento' => '2020-05-10',
            'tutor_principal_id' => null,
        ], $overrides);
    }

    public function test_full_identity_match(): void
    {
        $this->assertSame(
            TipoCoincidencia::IDENTIDAD_COMPLETA,
            (new PacienteDuplicateRule)->classify($this->input(), $this->candidate()),
        );
    }

    public function test_partial_identity_when_maternal_is_missing(): void
    {
        $this->assertSame(
            TipoCoincidencia::IDENTIDAD_PARCIAL,
            (new PacienteDuplicateRule)->classify(
                $this->input(['apellido_materno' => null]),
                $this->candidate(['apellido_materno' => null]),
            ),
        );
    }

    public function test_partial_identity_with_same_principal_tutor(): void
    {
        $this->assertSame(
            TipoCoincidencia::IDENTIDAD_PARCIAL_CON_TUTOR,
            (new PacienteDuplicateRule)->classify(
                $this->input([
                    'nombre' => 'otro',
                    'apellido_materno' => null,
                    'tutor_principal_id' => 9,
                ]),
                $this->candidate([
                    'nombre' => 'distinto',
                    'apellido_materno' => null,
                    'tutor_principal_id' => 9,
                ]),
            ),
        );
    }

    public function test_different_date_is_not_a_match(): void
    {
        $this->assertNull(
            (new PacienteDuplicateRule)->classify(
                $this->input(),
                $this->candidate(['fecha_nacimiento' => '2020-05-11']),
            ),
        );
    }
}
