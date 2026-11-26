<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profesionales asignados al diagnóstico.
     *
     * La cobertura por especialidad se valida contra el snapshot de requisitos;
     * el pivote conserva los profesionales asignados.
     */
    public function up(): void
    {
        Schema::create('atencion_diagnostico_profesional', function (Blueprint $table) {
            $table->foreignId('atencion_diagnostico_id')
                ->constrained('atencion_diagnostico')
                ->cascadeOnDelete();
            $table->foreignId('profesional_id')
                ->constrained('profesional')
                ->cascadeOnDelete();

            $table->primary(['atencion_diagnostico_id', 'profesional_id'], 'atencion_diagnostico_profesional_pk');
            $table->index('profesional_id', 'atencion_diagnostico_profesional_profesional_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atencion_diagnostico_profesional');
    }
};
