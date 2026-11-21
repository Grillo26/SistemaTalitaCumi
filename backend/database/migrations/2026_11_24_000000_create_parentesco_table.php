<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de parentescos (04.02/RNF-012).
     *
     * Los valores iniciales son una propuesta a validar con el cliente.
     */
    public function up(): void
    {
        Schema::create('parentesco', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->string('nombre_normalizado', 50)->unique();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestampsTz();

            $table->index(['activo', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parentesco');
    }
};
