<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Models\MotivoTurno;
use Illuminate\Database\Seeder;

/**
 * [Reprogramación y Cancelación] RF-003 / RNF-011.
 *
 * Solo crea los cinco motivos de la spec. `origen` y `descuenta_saldo`
 * quedan NULL a propósito: el negocio debe confirmarlos. No se pisan
 * configuraciones existentes.
 */
final class MotivosTurnoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $motivo) {
            MotivoTurno::query()->firstOrCreate(
                ['codigo' => $motivo['codigo']],
                [
                    'codigo' => $motivo['codigo'],
                    'nombre' => $motivo['nombre'],
                    'aplica_reprogramar' => true,
                    'aplica_cancelar' => true,
                    'origen' => null,
                    'descuenta_saldo' => null,
                    'requiere_texto' => $motivo['requiere_texto'],
                    'activo' => true,
                    'orden' => $motivo['orden'],
                ],
            );
        }
    }

    /**
     * @return list<array{codigo: string, nombre: string, requiere_texto: bool, orden: int}>
     */
    private function catalog(): array
    {
        return [
            [
                'codigo' => 'enfermedad_paciente',
                'nombre' => 'Enfermedad del paciente',
                'requiere_texto' => false,
                'orden' => 10,
            ],
            [
                'codigo' => 'ausencia_profesional',
                'nombre' => 'Ausencia del profesional',
                'requiere_texto' => false,
                'orden' => 20,
            ],
            [
                'codigo' => 'feriado',
                'nombre' => 'Feriado',
                'requiere_texto' => false,
                'orden' => 30,
            ],
            [
                'codigo' => 'motivo_familiar',
                'nombre' => 'Motivo familiar',
                'requiere_texto' => false,
                'orden' => 40,
            ],
            [
                'codigo' => 'otro',
                'nombre' => 'Otro',
                'requiere_texto' => true,
                'orden' => 50,
            ],
        ];
    }
}
