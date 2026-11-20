<?php

namespace Tests\Unit\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Enums\UsoAmbiente;
use App\Modules\Organizacion\Exceptions\EspacioInactivoException;
use App\Modules\Organizacion\Exceptions\EspacioSucursalIncorrectaException;
use App\Modules\Organizacion\Exceptions\InactiveCatalogException;
use App\Modules\Organizacion\Exceptions\StatusReasonRequiredException;
use App\Modules\Organizacion\Exceptions\StatusUnchangedException;
use App\Modules\Organizacion\Exceptions\TipoSalaInUseException;
use App\Modules\Organizacion\Rules\CatalogAvailabilityRule;
use App\Modules\Organizacion\Rules\SalaIdentityRule;
use App\Modules\Organizacion\Rules\SpaceInitialStatusRule;
use App\Modules\Organizacion\Rules\SpaceNameNormalizationRule;
use App\Modules\Organizacion\Rules\SpaceOperationalStatusRule;
use App\Modules\Organizacion\Rules\SpaceStatusChangeRule;
use App\Modules\Organizacion\Rules\SpaceSucursalCoherenceRule;
use App\Modules\Organizacion\Rules\TipoSalaDeactivationRule;
use PHPUnit\Framework\TestCase;

final class SalasAmbientesRulesTest extends TestCase
{
    public function test_space_name_normalization(): void
    {
        $this->assertSame('Sala Ositos', SpaceNameNormalizationRule::normalize('  Sala   Ositos  '));
        $this->assertSame('sala ositos', SpaceNameNormalizationRule::fold('  Sala   Ositos  '));
    }

    public function test_initial_status_defaults_to_active(): void
    {
        $this->assertTrue(SpaceInitialStatusRule::resolve(null));
        $this->assertTrue(SpaceInitialStatusRule::resolve(true));
        $this->assertFalse(SpaceInitialStatusRule::resolve(false));
    }

    public function test_sala_identity_key_and_uniqueness(): void
    {
        $key = SalaIdentityRule::key(1, 2, 3);

        $this->assertSame('1:2:3', $key);
        $this->assertTrue(SalaIdentityRule::isUnique($key, ['1:2:4']));
        $this->assertFalse(SalaIdentityRule::isUnique($key, ['1:2:3']));
    }

    public function test_inactive_catalog_is_rejected(): void
    {
        $this->expectException(InactiveCatalogException::class);

        CatalogAvailabilityRule::assertActive(false, 'turno', 'turno_catalogo_id', 'Mañana');
    }

    public function test_tipo_sala_with_active_salas_cannot_be_deactivated(): void
    {
        $this->expectException(TipoSalaInUseException::class);

        TipoSalaDeactivationRule::assertCanDeactivate(true, 'Preescolar', 2);
    }

    public function test_status_reason_is_required_on_deactivation(): void
    {
        $this->expectException(StatusReasonRequiredException::class);

        (new SpaceStatusChangeRule)->reason('   ');
    }

    public function test_status_reason_is_trimmed(): void
    {
        $this->assertSame('Cierre temporal', (new SpaceStatusChangeRule)->reason('  Cierre temporal  '));
    }

    public function test_status_must_change(): void
    {
        $this->expectException(StatusUnchangedException::class);

        (new SpaceStatusChangeRule)->assertDifferent(false, false);
    }

    public function test_inactive_space_is_rejected(): void
    {
        $this->expectException(EspacioInactivoException::class);

        SpaceOperationalStatusRule::assertAvailable(false, 'sala', 1, 'Sala Ositos');
    }

    public function test_active_space_is_accepted(): void
    {
        SpaceOperationalStatusRule::assertAvailable(true, 'sala', 1, 'Sala Ositos');

        $this->assertTrue(true);
    }

    public function test_space_branch_mismatch_is_rejected(): void
    {
        $this->expectException(EspacioSucursalIncorrectaException::class);

        SpaceSucursalCoherenceRule::assertSameBranch(2, 1, 'sala', 10);
    }

    public function test_ambiente_usage_values_and_labels(): void
    {
        $this->assertSame(
            ['terapia_individual', 'entrevista', 'diagnostico', 'mixto'],
            UsoAmbiente::values(),
        );
        $this->assertSame('Diagnóstico', UsoAmbiente::Diagnostico->label());
    }
}
