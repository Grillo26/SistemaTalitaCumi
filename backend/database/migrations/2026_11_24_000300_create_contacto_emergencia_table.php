<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contactos de emergencia adicionales por paciente (04.02/RF-012).
     *
     * Son personas distintas de los tutores. Pertenecen a un paciente y se
     * eliminan físicamente porque no son una entidad global ni tienen
     * relaciones históricas; la bitácora conserva el snapshot.
     */
    public function up(): void
    {
        Schema::create('contacto_emergencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('paciente')->cascadeOnDelete();
            $table->string('nombre_completo', 200);
            $table->string('nombre_normalizado', 200);
            $table->foreignId('parentesco_id')->constrained('parentesco')->restrictOnDelete();
            $table->string('telefono', 30);
            $table->string('telefono_normalizado', 30);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();

            $table->index('paciente_id');
            $table->index('parentesco_id');
            $table->index(['paciente_id', 'nombre_normalizado']);
            $table->index('telefono_normalizado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacto_emergencia');
    }
};
