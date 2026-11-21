<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo global de áreas.
     *
     * En esta etapa se crea como prerequisito de solo lectura para
     * `sala.area_id`. La administración de áreas y colores pertenece al
     * módulo 05 (Catálogo de servicios y tarifas).
     */
    public function up(): void
    {
        Schema::create('area', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('color', 7)->default('#6B7280');
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area');
    }
};
