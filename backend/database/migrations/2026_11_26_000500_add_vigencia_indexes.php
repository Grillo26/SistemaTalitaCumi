<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de lectura para el panel y reportes de vigencias (RNF-001).
 *
 * No agrega columnas ni cambia estados: solo acelera los filtros por
 * sucursal/paciente y fecha de fin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripcion_sala', function (Blueprint $table): void {
            $table->index(['sucursal_id', 'fecha_fin'], 'inscripcion_sala_sucursal_fin_idx');
            $table->index(['paciente_id', 'fecha_fin'], 'inscripcion_sala_paciente_fin_idx');
        });

        Schema::table('paquete_sesiones', function (Blueprint $table): void {
            $table->index(['sucursal_id', 'fecha_fin'], 'paquete_sesiones_sucursal_fin_idx');
            $table->index(['paciente_id', 'fecha_fin'], 'paquete_sesiones_paciente_fin_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inscripcion_sala', function (Blueprint $table): void {
            $table->dropIndex('inscripcion_sala_sucursal_fin_idx');
            $table->dropIndex('inscripcion_sala_paciente_fin_idx');
        });

        Schema::table('paquete_sesiones', function (Blueprint $table): void {
            $table->dropIndex('paquete_sesiones_sucursal_fin_idx');
            $table->dropIndex('paquete_sesiones_paciente_fin_idx');
        });
    }
};
