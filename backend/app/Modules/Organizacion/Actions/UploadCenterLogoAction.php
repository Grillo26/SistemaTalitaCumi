<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Events\ParameterUpdated;
use App\Modules\Organizacion\Exceptions\ParameterConcurrencyException;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Support\ParameterSnapshot;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Subida del logo del centro (RF-009).
 *
 * El archivo se guarda en el disco público y el parámetro `centro.logo`
 * conserva la ruta relativa. El reemplazo se audita como EDITAR.
 */
final class UploadCenterLogoAction
{
    public const PARAMETER_KEY = 'centro.logo';

    public const DIRECTORY = 'centros';

    public function __invoke(
        UploadedFile $file,
        int $expectedVersion,
        ?string $motivo,
        ?int $actorId,
    ): Parametro {
        /** @var array{0: Parametro, 1: string|null} $result */
        $result = DB::transaction(function () use ($file, $expectedVersion, $motivo, $actorId): array {
            $parametro = Parametro::query()
                ->where('clave', self::PARAMETER_KEY)
                ->lockForUpdate()
                ->first();

            if ($parametro === null) {
                throw new ParameterNotFoundException(self::PARAMETER_KEY);
            }

            if ($parametro->version !== $expectedVersion) {
                throw new ParameterConcurrencyException;
            }

            $before = ParameterSnapshot::from($parametro);
            $previous = $parametro->valor;
            $path = $file->store(self::DIRECTORY, 'public');

            if (! is_string($path) || $path === '') {
                throw new RuntimeException('No se pudo almacenar el logo del centro.');
            }

            try {
                $parametro->valor = $path;
                $parametro->version = $parametro->version + 1;
                $parametro->save();
                $parametro->refresh();

                event(new ParameterUpdated(
                    parametroId: $parametro->id,
                    before: $before,
                    after: ParameterSnapshot::from($parametro),
                    changedFields: ['valor'],
                    motivo: $motivo,
                    actorId: $actorId,
                ));
            } catch (\Throwable $exception) {
                Storage::disk('public')->delete($path);

                throw $exception;
            }

            return [$parametro, $previous];
        });

        [$parametro, $previous] = $result;

        if (is_string($previous) && $previous !== '' && $previous !== $parametro->valor) {
            Storage::disk('public')->delete($previous);
        }

        return $parametro;
    }
}
