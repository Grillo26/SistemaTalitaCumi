<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Salas grupales (RF-004/RF-005).
     *
     * La tabla no existe en la base real; esta migración crea la versión
     * nueva con FKs a catálogos, área, sucursal y versión de concurrencia.
     */
    public function up(): void
    {
        Schema::create('sala', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->foreignId('area_id')->constrained('area')->restrictOnDelete();
            $table->foreignId('tipo_sala_id')->constrained('tipo_sala')->restrictOnDelete();
            $table->foreignId('turno_catalogo_id')->constrained('turno_catalogo')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();

            $table->unique(
                ['sucursal_id', 'tipo_sala_id', 'turno_catalogo_id'],
                'sala_identidad_unica',
            );
            $table->index('tipo_sala_id');
            $table->index('turno_catalogo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sala');
    }
};
