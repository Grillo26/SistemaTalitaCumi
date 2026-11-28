<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Models\MotivoTurno;
use App\Modules\AgendaTurnos\Support\DiagnosticosModule;
use Illuminate\Database\Seeder;

/**
 * Motivo de catálogo para cancelar los turnos futuros de un diagnóstico.
 *
 * No pisa configuraciones existentes: si el motivo ya existe, se conserva.
 */
final class DiagnosticoMotivoTurnoSeeder extends Seeder
{
    public function run(): void
    {
        MotivoTurno::query()->firstOrCreate(
            ['codigo' => DiagnosticosModule::MOTIVO_CANCELACION],
            [
                'codigo' => DiagnosticosModule::MOTIVO_CANCELACION,
                'nombre' => 'Cancelación del diagnóstico',
                'aplica_reprogramar' => false,
                'aplica_cancelar' => true,
                'origen' => null,
                'descuenta_saldo' => null,
                'requiere_texto' => false,
                'activo' => true,
                'orden' => 60,
            ],
        );
    }
}
