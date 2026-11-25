<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asociación paciente–tutor (04.01/RF-010, 04.02/RF-004/RN-003).
     *
     * Exactamente un tutor principal por paciente. La regla de “al menos uno
     * en pacientes activos” se valida en la aplicación.
     */
    public function up(): void
    {
        Schema::create('paciente_tutor', function (Blueprint $table) {
            $table->foreignId('paciente_id')->constrained('paciente')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('tutor')->cascadeOnDelete();
            $table->boolean('es_principal')->default(false);

            $table->primary(['paciente_id', 'tutor_id']);
            $table->index('tutor_id');
        });

        DB::statement(
            'CREATE UNIQUE INDEX paciente_tutor_principal_uk ON paciente_tutor (paciente_id) WHERE es_principal'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_tutor');
    }
};
