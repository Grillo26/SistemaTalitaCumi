<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo configurable de tipos de servicio (RF-C05 / RN-C09).
     *
     * El módulo 05 lo administra; no hay valores fijos en código.
     */
    public function up(): void
    {
        Schema::create('tipo_servicio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
        });

        DB::statement(
            'CREATE UNIQUE INDEX tipo_servicio_nombre_normalizado_uk '
            .'ON tipo_servicio ((lower(trim(nombre))))',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_servicio');
    }
};
