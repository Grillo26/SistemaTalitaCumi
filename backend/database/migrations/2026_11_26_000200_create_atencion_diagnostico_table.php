<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Diagnóstico o evaluación como atención (módulo 06, submódulo 03).
     */
    public function up(): void
    {
        Schema::create('atencion_diagnostico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('paciente')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained('servicio')->restrictOnDelete();
            $table->foreignId('tipo_diagnostico_id')->constrained('tipo_diagnostico')->restrictOnDelete();
            $table->foreignId('tarifa_id')->constrained('tarifa')->restrictOnDelete();
            $table->foreignId('categoria_tarifa_id')->constrained('categoria_tarifa')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('monto_esperado', 12, 2);
            $table->foreignId('entrevista_previa_id')
                ->nullable()
                ->constrained('atencion_entrevista')
                ->restrictOnDelete();
            $table->boolean('omitio_entrevista')->default(false);
            $table->text('motivo_omision_entrevista')->nullable();
            $table->string('estado_general', 20)->default('incompleto');
            $table->string('estado', 20)->default('activa');
            $table->text('notas_admin')->nullable();
            $table->timestampsTz();

            $table->index('estado', 'atencion_diagnostico_estado_idx');
            $table->index(['sucursal_id', 'fecha_inicio', 'fecha_fin'], 'atencion_diagnostico_sucursal_fechas_idx');
            $table->index(['paciente_id', 'estado'], 'atencion_diagnostico_paciente_estado_idx');
            $table->index('tipo_diagnostico_id', 'atencion_diagnostico_tipo_idx');
            $table->index('entrevista_previa_id', 'atencion_diagnostico_entrevista_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE atencion_diagnostico '
                ."ADD CONSTRAINT atencion_diagnostico_estado_valido CHECK (estado IN ('activa','retirada'))",
            );
            DB::statement(
                'ALTER TABLE atencion_diagnostico '
                .'ADD CONSTRAINT atencion_diagnostico_estado_general_valido '
                ."CHECK (estado_general IN ('incompleto','en_proceso','completo','cancelado'))",
            );
            DB::statement(
                'ALTER TABLE atencion_diagnostico '
                .'ADD CONSTRAINT atencion_diagnostico_fechas_coherentes CHECK (fecha_fin >= fecha_inicio)',
            );
            DB::statement(
                'ALTER TABLE atencion_diagnostico '
                .'ADD CONSTRAINT atencion_diagnostico_monto_no_negativo CHECK (monto_esperado >= 0)',
            );
            DB::statement(
                'ALTER TABLE atencion_diagnostico '
                .'ADD CONSTRAINT atencion_diagnostico_omision_coherente CHECK ('
                .'(entrevista_previa_id IS NOT NULL AND omitio_entrevista = FALSE AND motivo_omision_entrevista IS NULL) '
                .'OR (entrevista_previa_id IS NULL AND omitio_entrevista = TRUE)'
                .')',
            );
            DB::statement(
                'ALTER TABLE atencion_diagnostico '
                .'ADD CONSTRAINT atencion_diagnostico_motivo_omision_max '
                .'CHECK (motivo_omision_entrevista IS NULL OR char_length(motivo_omision_entrevista) <= 500)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atencion_diagnostico');
    }
};
