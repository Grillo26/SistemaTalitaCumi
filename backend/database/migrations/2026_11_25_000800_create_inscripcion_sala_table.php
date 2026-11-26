<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inscripciones a salas grupales (módulo 06 / submódulo 01).
     *
     * La tabla es la definida en el schema de referencia `inscripcion_sala`.
     * No se crea una tabla genérica `inscripciones`: los paquetes de sesiones
     * y las atenciones de diagnóstico conservan sus tablas propias.
     */
    public function up(): void
    {
        Schema::create('inscripcion_sala', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('paciente')->restrictOnDelete();
            $table->foreignId('tutor_responsable_id')->constrained('tutor')->restrictOnDelete();
            $table->foreignId('sala_id')->constrained('sala')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained('servicio')->restrictOnDelete();
            $table->foreignId('tarifa_id')->constrained('tarifa')->restrictOnDelete();
            $table->foreignId('categoria_tarifa_id')->constrained('categoria_tarifa')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('monto_esperado', 12, 2);
            $table->string('estado', 20)->default('activa');
            $table->foreignId('inscripcion_origen_id')
                ->nullable()
                ->constrained('inscripcion_sala')
                ->restrictOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestampsTz();

            $table->index('estado', 'inscripcion_sala_estado_idx');
            $table->index(['fecha_inicio', 'fecha_fin'], 'inscripcion_sala_fechas_idx');
            $table->index(['sucursal_id', 'fecha_inicio', 'fecha_fin'], 'inscripcion_sala_sucursal_periodo_idx');
            $table->index(['sala_id', 'fecha_inicio', 'fecha_fin'], 'inscripcion_sala_sala_periodo_idx');
            $table->index(['paciente_id', 'estado'], 'inscripcion_sala_paciente_estado_idx');
            $table->index('inscripcion_origen_id', 'inscripcion_sala_origen_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

            DB::statement(
                'ALTER TABLE inscripcion_sala '
                ."ADD CONSTRAINT inscripcion_sala_estado_valido CHECK (estado IN ('activa','en_pausa','vencida','renovada','retirada'))",
            );

            DB::statement(
                'ALTER TABLE inscripcion_sala '
                .'ADD CONSTRAINT inscripcion_sala_fechas_coherentes CHECK (fecha_fin >= fecha_inicio)',
            );

            DB::statement(
                'ALTER TABLE inscripcion_sala '
                .'ADD CONSTRAINT inscripcion_sala_monto_no_negativo CHECK (monto_esperado >= 0)',
            );

            DB::statement(<<<'SQL'
                ALTER TABLE inscripcion_sala
                ADD CONSTRAINT inscripcion_sala_sin_solapamiento
                EXCLUDE USING gist (
                    paciente_id WITH =,
                    sala_id WITH =,
                    daterange(fecha_inicio, fecha_fin, '[]') WITH &&
                )
                WHERE (estado IN ('activa', 'en_pausa'))
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripcion_sala');
    }
};
