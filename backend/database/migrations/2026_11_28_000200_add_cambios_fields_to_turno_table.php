<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo 07 — Cambios de turnos (7.3).
     *
     * Motivo estructurado, snapshot de consumo y usuario de cancelación. No
     * se cambia el CHECK de estado: `reprogramado` ya existe desde 7.1/7.2.
     */
    public function up(): void
    {
        Schema::table('turno', function (Blueprint $table) {
            $table->foreignId('motivo_turno_id')
                ->nullable()
                ->constrained('motivo_turno')
                ->restrictOnDelete();
            $table->text('motivo_detalle')->nullable();
            $table->boolean('consume_saldo')->nullable();
            $table->foreignId('cancelado_por_usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['sucursal_id', 'fecha', 'estado'], 'idx_turno_sucursal_fecha_estado');
            $table->index('motivo_turno_id', 'idx_turno_motivo');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE turno '
                .'ADD CONSTRAINT turno_consume_saldo_solo_cancelado CHECK ('
                .'consume_saldo IS NULL OR estado = \'cancelado\''
                .')',
            );

            DB::statement(<<<'SQL'
                CREATE UNIQUE INDEX turno_origen_unico
                ON turno (turno_origen_id)
                WHERE turno_origen_id IS NOT NULL
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS turno_origen_unico');
            DB::statement('ALTER TABLE turno DROP CONSTRAINT IF EXISTS turno_consume_saldo_solo_cancelado');
        }

        Schema::table('turno', function (Blueprint $table) {
            $table->dropIndex('idx_turno_motivo');
            $table->dropIndex('idx_turno_sucursal_fecha_estado');
            $table->dropConstrainedForeignId('cancelado_por_usuario_id');
            $table->dropConstrainedForeignId('motivo_turno_id');
            $table->dropColumn(['motivo_detalle', 'consume_saldo']);
        });
    }
};
