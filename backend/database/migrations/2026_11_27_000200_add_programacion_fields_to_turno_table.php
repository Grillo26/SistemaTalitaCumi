<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo 07 — Programación de turnos (7.2).
     *
     * Vínculo explícito turno-inscripción a sala, lote de generación y
     * trazabilidad de la cancelación por regeneración. Refuerzos en base de
     * datos: unicidad por origen/fecha/hora y exclusión de solapamiento del
     * paciente (RF-004 / RT-001 / RT-002).
     */
    public function up(): void
    {
        Schema::table('turno', function (Blueprint $table) {
            $table->foreignId('inscripcion_sala_id')
                ->nullable()
                ->constrained('inscripcion_sala')
                ->restrictOnDelete();
            $table->foreignId('lote_generacion_id')
                ->nullable()
                ->constrained('turno_lote_generacion')
                ->nullOnDelete();
            $table->foreignId('cancelado_por_lote_id')
                ->nullable()
                ->constrained('turno_lote_generacion')
                ->nullOnDelete();
            $table->string('cancelacion_origen', 30)->nullable();
            $table->timestampTz('cancelado_en')->nullable();

            $table->index('inscripcion_sala_id', 'idx_turno_inscripcion_sala');
            $table->index('lote_generacion_id', 'idx_turno_lote_generacion');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

            DB::statement(
                'ALTER TABLE turno '
                .'ADD CONSTRAINT turno_inscripcion_sala_origen CHECK ('
                .'inscripcion_sala_id IS NULL OR ('
                .'sala_id IS NOT NULL AND paquete_sesiones_id IS NULL AND atencion_diagnostico_id IS NULL'
                .'))',
            );

            DB::statement(
                'ALTER TABLE turno '
                .'ADD CONSTRAINT turno_cancelacion_origen_valida CHECK ('
                .'cancelacion_origen IS NULL OR cancelacion_origen IN (\'regeneracion\',\'manual\')'
                .')',
            );

            DB::statement(<<<'SQL'
                CREATE UNIQUE INDEX turno_inscripcion_sala_unico
                ON turno (inscripcion_sala_id, fecha, hora_inicio)
                WHERE inscripcion_sala_id IS NOT NULL
                  AND estado NOT IN ('cancelado', 'reprogramado')
            SQL);

            DB::statement(<<<'SQL'
                CREATE UNIQUE INDEX turno_paquete_unico
                ON turno (paquete_sesiones_id, fecha, hora_inicio)
                WHERE paquete_sesiones_id IS NOT NULL
                  AND estado NOT IN ('cancelado', 'reprogramado')
            SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE turno
                ADD CONSTRAINT turno_paciente_sin_solapamiento
                EXCLUDE USING gist (
                    paciente_id WITH =,
                    tsrange(fecha + hora_inicio, fecha + hora_fin, '[)') WITH &&
                )
                WHERE (estado NOT IN ('cancelado', 'reprogramado'))
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE turno DROP CONSTRAINT IF EXISTS turno_paciente_sin_solapamiento');
            DB::statement('DROP INDEX IF EXISTS turno_paquete_unico');
            DB::statement('DROP INDEX IF EXISTS turno_inscripcion_sala_unico');
            DB::statement('ALTER TABLE turno DROP CONSTRAINT IF EXISTS turno_cancelacion_origen_valida');
            DB::statement('ALTER TABLE turno DROP CONSTRAINT IF EXISTS turno_inscripcion_sala_origen');
        }

        Schema::table('turno', function (Blueprint $table) {
            $table->dropIndex('idx_turno_lote_generacion');
            $table->dropIndex('idx_turno_inscripcion_sala');
            $table->dropConstrainedForeignId('cancelado_por_lote_id');
            $table->dropConstrainedForeignId('lote_generacion_id');
            $table->dropConstrainedForeignId('inscripcion_sala_id');
            $table->dropColumn(['cancelacion_origen', 'cancelado_en']);
        });
    }
};
