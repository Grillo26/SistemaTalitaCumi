<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Directorio global de profesionales (03/RF-D01, 03/RN-01, 03/RN-19).
     *
     * Profesional NO es usuario del sistema: no hay FK a users ni rol Spatie.
     * El documento (CI) es obligatorio y único en todo el sistema. No usa
     * SoftDeletes: se activa/desactiva con motivo (03/RN-04, 03/RN-20).
     */
    public function up(): void
    {
        Schema::create('profesional', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo', 200);
            $table->string('ci', 30);
            $table->string('telefono', 30)->nullable();
            $table->string('telefono_2', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('matricula', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->text('motivo_estado')->nullable();
            $table->timestampTz('estado_cambiado_en')->nullable();
            $table->timestampsTz();

            $table->index('activo');
            $table->index('nombre_completo');
        });

        DB::statement(
            'CREATE UNIQUE INDEX profesional_ci_normalizado_uk ON profesional ((lower(trim(ci))))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('profesional');
    }
};
