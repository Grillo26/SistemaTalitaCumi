<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo configurable de acciones auditadas (02/RF-B03).
     *
     * No es la bitácora: describe qué acciones existen, a qué módulo
     * pertenecen y si son críticas. La bitácora sigue en `bitacora`.
     */
    public function up(): void
    {
        Schema::create('accion_auditada', function (Blueprint $table) {
            $table->id();
            $table->string('modulo', 50);
            $table->string('entidad', 100);
            $table->string('accion', 100);
            $table->boolean('critico')->default(false);
            $table->boolean('activo')->default(true);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['modulo', 'entidad', 'accion']);
            $table->index(['modulo', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accion_auditada');
    }
};
