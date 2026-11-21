<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo global de especialidades (03/RF-E01, 03/RN-06).
     *
     * El nombre es único sin distinguir mayúsculas ni espacios externos.
     * No usa SoftDeletes: se activa/desactiva con motivo (03/RN-04, 03/RN-20).
     */
    public function up(): void
    {
        Schema::create('especialidad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->text('motivo_estado')->nullable();
            $table->timestampTz('estado_cambiado_en')->nullable();
            $table->timestampsTz();

            $table->index('activo');
        });

        DB::statement(
            'CREATE UNIQUE INDEX especialidad_nombre_normalizado_uk ON especialidad ((lower(trim(nombre))))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidad');
    }
};
