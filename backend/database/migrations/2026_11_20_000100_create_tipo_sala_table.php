<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo global de tipos de sala (RF-001/RF-002).
     */
    public function up(): void
    {
        Schema::create('tipo_sala', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
        });

        DB::statement('CREATE UNIQUE INDEX tipo_sala_nombre_normalizado_uk ON tipo_sala ((lower(trim(nombre))))');
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_sala');
    }
};
