<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Observaciones fechadas de la ficha de ingreso (04.03 Fase C, RF-006).
     *
     * Solo inserción: no se editan ni se borran. Una corrección es una nueva
     * observación que referencia a la anterior (RN-003).
     */
    public function up(): void
    {
        Schema::create('ficha_ingreso_observacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ficha_ingreso_id')->constrained('ficha_ingreso')->restrictOnDelete();
            $table->date('fecha');
            $table->text('texto');
            $table->foreignId('observacion_referencia_id')
                ->nullable()
                ->constrained('ficha_ingreso_observacion')
                ->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['ficha_ingreso_id', 'fecha']);
            $table->index('observacion_referencia_id');
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ficha_ingreso_observacion');
    }
};
