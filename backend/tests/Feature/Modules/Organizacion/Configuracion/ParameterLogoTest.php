<?php

namespace Tests\Feature\Modules\Organizacion\Configuracion;

use App\Modules\Organizacion\Models\BitacoraEntry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ParameterLogoTest extends ConfiguracionTestCase
{
    public function test_superadmin_uploads_logo_and_audits(): void
    {
        Storage::fake('public');

        $user = $this->actingAsSuperadmin();
        $parametro = $this->parametro('centro.logo');

        $response = $this->post(
            '/api/organizacion/configuracion/logo',
            [
                'logo' => UploadedFile::fake()->image('logo.png', 100, 100),
                'version' => $parametro->version,
                'motivo' => 'Logo oficial del centro',
            ],
            ['Accept' => 'application/json'],
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.clave', 'centro.logo')
            ->assertJsonPath('data.version', 2);

        $path = $parametro->fresh()->valor;

        $this->assertNotSame('', $path);
        $this->assertStringStartsWith('centros/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertNotNull($response->json('data.logo_url'));

        $audit = BitacoraEntry::query()
            ->where('entidad', 'parametro')
            ->where('entidad_id', $parametro->id)
            ->where('accion', 'EDITAR')
            ->sole();

        $this->assertSame($user->id, $audit->usuario_id);
        $this->assertSame('', $audit->datos_anteriores['valor']);
        $this->assertSame($path, $audit->datos_nuevos['valor']);
        $this->assertSame('Logo oficial del centro', $audit->motivo);
    }

    public function test_replacing_logo_deletes_previous_file(): void
    {
        Storage::fake('public');
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('centro.logo');

        $this->post('/api/organizacion/configuracion/logo', [
            'logo' => UploadedFile::fake()->image('logo-1.png', 80, 80),
            'version' => $parametro->version,
        ], ['Accept' => 'application/json'])->assertOk();

        $firstPath = $parametro->fresh()->valor;
        Storage::disk('public')->assertExists($firstPath);

        $this->post('/api/organizacion/configuracion/logo', [
            'logo' => UploadedFile::fake()->image('logo-2.png', 90, 90),
            'version' => $parametro->fresh()->version,
        ], ['Accept' => 'application/json'])->assertOk();

        $secondPath = $parametro->fresh()->valor;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_logo_version_conflict_returns_409(): void
    {
        Storage::fake('public');
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('centro.logo');

        $this->post('/api/organizacion/configuracion/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 80, 80),
            'version' => $parametro->version + 5,
        ], ['Accept' => 'application/json'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PARAMETRO_VERSION_CONFLICT');
    }

    public function test_invalid_file_is_rejected(): void
    {
        Storage::fake('public');
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('centro.logo');

        $this->post('/api/organizacion/configuracion/logo', [
            'logo' => UploadedFile::fake()->create('logo.pdf', 20, 'application/pdf'),
            'version' => $parametro->version,
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('logo');
    }

    public function test_administracion_cannot_upload_logo(): void
    {
        Storage::fake('public');
        $this->actingAsRole('administracion');
        $parametro = $this->parametro('centro.logo');

        $this->post('/api/organizacion/configuracion/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 80, 80),
            'version' => $parametro->version,
        ], ['Accept' => 'application/json'])->assertForbidden();
    }
}
