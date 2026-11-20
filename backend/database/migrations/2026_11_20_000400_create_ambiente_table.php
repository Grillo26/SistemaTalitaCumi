<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ambientes de atención individual (RF-006/RF-007).
     */
    public function up(): void
    {
        Schema::create('ambiente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->enum('uso', ['terapia_individual', 'entrevista', 'diagnostico', 'mixto']);
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();

            $table->index('sucursal_id');
            $table->index('uso');
        });

        DB::statement('CREATE UNIQUE INDEX ambiente_sucursal_nombre_normalizado_uk ON ambiente (sucursal_id, (lower(trim(nombre))))');
    }

    public function down(): void
    {
        Schema::dropIfExists('ambiente');
    }
};
