<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo 07 — Agenda y turnos.
     *
     * Crea la tabla física `turno` y el pivote `turno_profesional` que el
     * schema de referencia ya documentaba. El submódulo 7.1 solo lee; 7.2 a
     * 7.5 serán los que escriban turnos.
     */
    public function up(): void
    {
        Schema::create('turno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->foreignId('paciente_id')->constrained('paciente')->restrictOnDelete();
            $table->foreignId('sala_id')->nullable()->constrained('sala')->restrictOnDelete();
            $table->foreignId('paquete_sesiones_id')
                ->nullable()
                ->constrained('paquete_sesiones')
                ->restrictOnDelete();
            $table->foreignId('atencion_diagnostico_id')
                ->nullable()
                ->constrained('atencion_diagnostico')
                ->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained('servicio')->restrictOnDelete();
            $table->foreignId('profesional_id')->nullable()->constrained('profesional')->restrictOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedInteger('duracion_minutos')->default(50);
            $table->string('estado', 20)->default('programado');
            $table->text('motivo_cancelacion')->nullable();
            $table->foreignId('turno_origen_id')
                ->nullable()
                ->constrained('turno')
                ->restrictOnDelete();
            $table->foreignId('usuario_registro_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('usuario_asistencia_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampTz('asistencia_registrada_en')->nullable();
            $table->timestampsTz();

            $table->index('fecha', 'idx_turno_fecha');
            $table->index(['sucursal_id', 'fecha'], 'idx_turno_sucursal_fecha');
            $table->index('paciente_id', 'idx_turno_paciente');
            $table->index('profesional_id', 'idx_turno_profesional');
            $table->index('estado', 'idx_turno_estado');
            $table->index(['sala_id', 'fecha'], 'idx_turno_sala_fecha');
        });

        Schema::create('turno_profesional', function (Blueprint $table) {
            $table->foreignId('turno_id')->constrained('turno')->cascadeOnDelete();
            $table->foreignId('profesional_id')->constrained('profesional')->cascadeOnDelete();

            $table->primary(['turno_id', 'profesional_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE turno '
                ."ADD CONSTRAINT turno_estado_valido CHECK (estado IN ('programado','asistio','falto','cancelado','reprogramado'))",
            );

            DB::statement(
                'ALTER TABLE turno '
                .'ADD CONSTRAINT turno_horas_coherentes CHECK (hora_fin > hora_inicio)',
            );

            DB::statement(
                'ALTER TABLE turno '
                .'ADD CONSTRAINT turno_duracion_positiva CHECK (duracion_minutos > 0)',
            );

            DB::statement(
                'ALTER TABLE turno '
                .'ADD CONSTRAINT turno_un_solo_origen CHECK ('
                .'(sala_id IS NOT NULL AND paquete_sesiones_id IS NULL AND atencion_diagnostico_id IS NULL) OR '
                .'(sala_id IS NULL AND paquete_sesiones_id IS NOT NULL AND atencion_diagnostico_id IS NULL) OR '
                .'(sala_id IS NULL AND paquete_sesiones_id IS NULL AND atencion_diagnostico_id IS NOT NULL)'
                .')',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('turno_profesional');
        Schema::dropIfExists('turno');
    }
};
