<?php

namespace Tests\Feature\Modules\Seguridad\Usuarios;

use App\Modules\Seguridad\Contracts\UserDirectoryContract;
use App\Modules\Seguridad\Exceptions\InactiveUserException;
use App\Modules\Seguridad\Exceptions\UserNotFoundException;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class UserDirectoryContractTest extends SeguridadTestCase
{
    public function test_exists_and_active_state_are_reported(): void
    {
        $active = $this->administracion();
        $inactive = $this->administracion(['activo' => false]);

        $directory = app(UserDirectoryContract::class);

        $this->assertTrue($directory->existe($active->id));
        $this->assertTrue($directory->estaActivo($active->id));
        $this->assertTrue($directory->existe($inactive->id));
        $this->assertFalse($directory->estaActivo($inactive->id));
        $this->assertFalse($directory->existe(999999));

        $directory->asegurarActivo($active->id);

        $this->assertTrue(true);
    }

    public function test_asegurar_activo_throws_for_inactive_user(): void
    {
        $user = $this->administracion(['activo' => false]);

        $this->expectException(InactiveUserException::class);

        app(UserDirectoryContract::class)->asegurarActivo($user->id);
    }

    public function test_asegurar_activo_throws_for_missing_user(): void
    {
        $this->expectException(UserNotFoundException::class);

        app(UserDirectoryContract::class)->asegurarActivo(999999);
    }
}
