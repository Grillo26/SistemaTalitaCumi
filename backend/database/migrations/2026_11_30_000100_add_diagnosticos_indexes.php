<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 07.05/RNF-001 / 07.05/RNF-009.
     *
     * Índices para la vista de coordinación y el reporte. No se agregan
     * columnas ni tablas: `atencion_diagnostico.estado_general` ya existe.
     */
    public function up(): void
    {
        Schema::table('turno', function (Blueprint $table): void {
            $table->index(
                ['atencion_diagnostico_id', 'fecha', 'hora_inicio'],
                'turno_atencion_diagnostico_fecha_idx',
            );
        });

        Schema::table('atencion_diagnostico', function (Blueprint $table): void {
            $table->index(
                ['sucursal_id', 'estado_general', 'fecha_inicio'],
                'atencion_diagnostico_sucursal_estado_general_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('turno', function (Blueprint $table): void {
            $table->dropIndex('turno_atencion_diagnostico_fecha_idx');
        });

        Schema::table('atencion_diagnostico', function (Blueprint $table): void {
            $table->dropIndex('atencion_diagnostico_sucursal_estado_general_idx');
        });
    }
};
