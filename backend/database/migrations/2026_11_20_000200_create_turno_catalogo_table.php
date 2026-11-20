<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo global de turnos operativos (RF-003).
     *
     * Se llama `turno_catalogo` para no colisionar con la tabla `turno`
     * de agenda del módulo 07.
     */
    public function up(): void
    {
        Schema::create('turno_catalogo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();

            $table->index('orden');
        });

        DB::statement('CREATE UNIQUE INDEX turno_catalogo_nombre_normalizado_uk ON turno_catalogo ((lower(trim(nombre))))');
    }

    public function down(): void
    {
        Schema::dropIfExists('turno_catalogo');
    }
};
