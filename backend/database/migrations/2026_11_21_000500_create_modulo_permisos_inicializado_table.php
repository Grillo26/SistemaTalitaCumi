<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca de matriz inicial por módulo (RF-R04).
     *
     * Evita que un seeder re-ejecutado vuelva a conceder permisos que el
     * Superadmin revocó desde la UI. El helper de seeders es autocurativo:
     * si detecta asignaciones previas, registra la marca y no reasigna.
     */
    public function up(): void
    {
        Schema::create('modulo_permisos_inicializado', function (Blueprint $table) {
            $table->string('modulo', 100)->primary();
            $table->timestampTz('inicializado_en')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulo_permisos_inicializado');
    }
};
