<?php

namespace Database\Seeders;

use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Support\PacienteNormalizer;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de parentescos (04.02/RNF-012).
 *
 * PROPUESTO - VALIDAR: el cliente debe confirmar la lista. El seeder es
 * idempotente y no pisa cambios: solo crea los faltantes por nombre normalizado.
 */
final class ParentescoCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $parentescos = [
            'Madre' => 10,
            'Padre' => 20,
            'Abuela' => 30,
            'Abuelo' => 40,
            'Tía' => 50,
            'Tío' => 60,
            'Hermana' => 70,
            'Hermano' => 80,
            'Madrina' => 90,
            'Padrino' => 100,
            'Apoderado/a' => 110,
            'Tutor/a legal' => 120,
            'Vecino/a' => 130,
            'Otro' => 140,
        ];

        foreach ($parentescos as $nombre => $orden) {
            Parentesco::query()->firstOrCreate(
                ['nombre_normalizado' => PacienteNormalizer::key($nombre)],
                [
                    'nombre' => $nombre,
                    'activo' => true,
                    'orden' => $orden,
                ],
            );
        }

        // Backfill del parentesco de referencia y de la relación desde el
        // texto legacy `tutor.parentesco`.
        $byName = Parentesco::query()->get()->keyBy(
            static fn (Parentesco $parentesco): string => PacienteNormalizer::key($parentesco->nombre),
        );

        Tutor::query()
            ->whereNull('parentesco_id')
            ->whereNotNull('parentesco')
            ->orderBy('id')
            ->each(function (Tutor $tutor) use ($byName): void {
                $parentesco = $byName->get(PacienteNormalizer::key($tutor->parentesco));

                if ($parentesco !== null) {
                    $tutor->forceFill(['parentesco_id' => $parentesco->id])->saveQuietly();
                }
            });

        PacienteTutor::query()
            ->whereNull('parentesco_id')
            ->orderBy('paciente_id')
            ->each(function (PacienteTutor $relacion): void {
                $parentescoId = Tutor::query()
                    ->whereKey($relacion->tutor_id)
                    ->value('parentesco_id');

                if ($parentescoId !== null) {
                    PacienteTutor::query()
                        ->where('paciente_id', $relacion->paciente_id)
                        ->where('tutor_id', $relacion->tutor_id)
                        ->update(['parentesco_id' => $parentescoId]);
                }
            });
    }
}
