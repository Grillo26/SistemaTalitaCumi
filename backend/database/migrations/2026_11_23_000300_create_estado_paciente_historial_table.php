<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de estados del paciente (04.01/RN-004, 04.01/RN-005).
     *
     * Solo inserción. Complementa la bitácora: guarda la transición de
     * dominio para que 06.05 pueda mostrarla sin leer la bitácora.
     */
    public function up(): void
    {
        Schema::create('estado_paciente_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('paciente')->restrictOnDelete();
            $table->enum('estado_anterior', ['activo', 'en_pausa', 'retirado'])->nullable();
            $table->enum('estado_nuevo', ['activo', 'en_pausa', 'retirado']);
            $table->text('motivo')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['paciente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estado_paciente_historial');
    }
};
