<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tutores del módulo 04.02 (núcleo).
     *
     * El tutor es global. No usa SoftDeletes: se activa/desactiva cuando
     * exista el flujo completo de 04.02. En esta entrega se crea activo.
     */
    public function up(): void
    {
        Schema::create('tutor', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo', 200);
            $table->string('ci', 20)->nullable();
            $table->string('telefono', 30);
            $table->string('telefono_alternativo', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('direccion')->nullable();
            $table->string('parentesco', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->text('motivo_estado')->nullable();
            $table->timestampTz('estado_cambiado_en')->nullable();
            $table->string('nombre_completo_normalizado', 200);
            $table->string('documento_normalizado', 20)->nullable();
            $table->string('telefono_normalizado', 30);
            $table->timestampsTz();

            $table->index('activo');
            $table->index('nombre_completo_normalizado');
            $table->index('telefono_normalizado');
            $table->unique('documento_normalizado', 'tutor_documento_normalizado_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor');
    }
};
