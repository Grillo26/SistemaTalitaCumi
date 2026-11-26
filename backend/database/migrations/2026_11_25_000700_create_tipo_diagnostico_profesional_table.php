<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Especialidades requeridas por tipo de diagnóstico (RF-D02 / RN-D03 / RN-D04).
     */
    public function up(): void
    {
        Schema::create('tipo_diagnostico_profesional', function (Blueprint $table) {
            $table->foreignId('tipo_diagnostico_id')
                ->constrained('tipo_diagnostico')
                ->cascadeOnDelete();
            $table->foreignId('especialidad_id')
                ->constrained('especialidad')
                ->cascadeOnDelete();
            $table->unsignedInteger('cantidad_minima')->default(1);

            $table->primary(['tipo_diagnostico_id', 'especialidad_id']);
            $table->index('especialidad_id', 'tipo_diagnostico_profesional_especialidad_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE tipo_diagnostico_profesional '
                .'ADD CONSTRAINT tipo_diagnostico_profesional_cantidad_positiva '
                .'CHECK (cantidad_minima >= 1)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_diagnostico_profesional');
    }
};
